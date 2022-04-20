<?php

namespace App\Controller\Admin;

use App\Helper\Utils\StringUtils;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\CSV\Writer;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Doctrine\ORM\Tools\Pagination\Paginator;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;

use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\PaginatorFactory;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityPaginator;
use function Symfony\Component\String\u;
use Symfony\Component\HttpFoundation\Response;

abstract class AdminCrudController extends AbstractCrudController
{



    abstract public function getName(): string;


    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular($this->getName())
            ->setEntityLabelInPlural($this->getName() . 's')
            ->setDateTimeFormat('yyyy-MM-dd HH:mm:ss')
            ->showEntityActionsInlined();
    }


    public function configureActions(Actions $actions): Actions
    {

        $exportIndex = Action::new('export', 'Export to csv')
            ->setIcon('fa fa-download')
            ->linkToCrudAction('export')
            ->setCssClass('btn btn-primary')
            ->createAsGlobalAction();

        return $actions
            ->add(Crud::PAGE_INDEX, $exportIndex)
            ->update(Crud::PAGE_INDEX, Action::NEW,  function (Action $action) {
                return $action->setIcon('fa fa-plus')->setLabel("Add a new " . $this->getName());
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT,  function (Action $action) {
                return $action->setIcon('fa fa-pencil')->setLabel(false);
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE,  function (Action $action) {
                return $action->setIcon('fa fa-trash')->setLabel(false);
            });
    }


    public function export(FilterFactory $filterFactory, AdminContext $context, EntityFactory $entityFactory)
    {
        $fields = $this->getFieldsExport();
        $h = fopen('php://output', 'r');
        $writer = $this->createWriter($fields);

        $filters = $filterFactory->create($context->getCrud()->getFiltersConfig(), $fields, $context->getEntity());
        $queryBuilder = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), $fields, $filters);

        $pageSize = 200;
        $currentPage = 1;

        do {
            $firstResult = ($currentPage - 1) * $pageSize;

            /** @var Query $query */
            $query = $queryBuilder
                ->setFirstResult($firstResult)
                ->setMaxResults($pageSize)
                ->getQuery();

            $paginator = new Paginator($query);

            if (($firstResult + $pageSize) < $paginator->count()) {
                $currentPage++;
            } else {
                $currentPage = 0;
            }

            $entities = $entityFactory->createCollection($context->getEntity(), $paginator->getIterator());
            $entityFactory->processFieldsForAll($entities, $fields);

            $entitiesArray = $entities->getIterator();
            foreach ($entitiesArray as $entityArray) {
                $this->addDataToWriter($writer, $entityArray);
            }
            $this->getDoctrine()->getManager()->clear();
        } while ($currentPage != 0);

        $writer->close();
        return new Response(stream_get_contents($h));
    }


    protected function addDataToWriter(Writer $writer, EntityDto $entity)
    {
        $fieldsEntity = $entity->getFields();
        $cellDatas = [];
        foreach ($fieldsEntity as $fieldEntity) {
            $cellDatas[] = WriterEntityFactory::createCell($fieldEntity->getFormattedValue());
        }
        $singleRowData = WriterEntityFactory::createRow($cellDatas);
        $writer->addRow($singleRowData);
    }



    protected function createWriter(FieldCollection $fields): Writer
    {
        $writer = WriterEntityFactory::createCSVWriter();
        $fileName = u('Export ' . $this->getName() . ' ' . date('Ymd His'))->snake() . '.csv';
        $writer->openToBrowser($fileName);

        $cellHeaders = [];
        foreach ($fields as $field) {
            $label = strlen($field->getLabel()) > 0 ? $field->getLabel() : StringUtils::humanizeString($field->getProperty());
            $cellHeaders[] = WriterEntityFactory::createCell($label);
        }
        $singleRow = WriterEntityFactory::createRow($cellHeaders);
        $writer->addRow($singleRow);
        return $writer;
    }



    protected function getFieldsExport(): FieldCollection
    {
        return FieldCollection::new($this->configureFields(Crud::PAGE_INDEX));
    }
}
