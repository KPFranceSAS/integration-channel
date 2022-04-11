<?php

namespace App\Controller\Admin;

use App\Helper\Utils\StringUtils;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use function Symfony\Component\String\u;

use Symfony\Component\HttpFoundation\Response;

abstract class AdminCrudController extends AbstractCrudController
{



    abstract public function getName(): string;




    public function configureActions(Actions $actions): Actions
    {
        $viewOrderIndex = Action::new(Action::DETAIL, '', 'fa fa-eye')
            ->linkToCrudAction(Action::DETAIL);

        $exportIndex = Action::new('export', 'Export to csv')
            ->setIcon('fa fa-download')
            ->linkToCrudAction('export')
            ->setCssClass('btn btn-primary')
            ->createAsGlobalAction();

        return $actions
            ->add(Crud::PAGE_INDEX, $exportIndex)
            ->add(Crud::PAGE_INDEX, $viewOrderIndex)
            ->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE, Action::EDIT);
    }


    public function export(FilterFactory $filterFactory, AdminContext $context, EntityFactory $entityFactory)
    {
        $fields = $this->getFieldsExport();
        $filters = $filterFactory->create($context->getCrud()->getFiltersConfig(), $fields, $context->getEntity());
        $queryBuilder = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), $fields, $filters);
        $entities = $entityFactory->createCollection($context->getEntity(), $queryBuilder->getQuery()->getResult());
        $entityFactory->processFieldsForAll($entities, $fields);
        $writer = WriterEntityFactory::createCSVWriter();
        $fileName = u('Export ' . $this->getName() . '_' . date('Ymd-His'))->snake() . '.csv';
        $writer->openToBrowser($fileName);
        $h = fopen('php://output', 'r');

        $cellHeaders = [];
        foreach ($fields as $field) {
            $label = strlen($field->getLabel()) > 0 ? $field->getLabel() : StringUtils::humanizeString($field->getProperty());
            $cellHeaders[] = WriterEntityFactory::createCell($label);
        }
        $singleRow = WriterEntityFactory::createRow($cellHeaders);
        $writer->addRow($singleRow);

        $entitiesArray = $entities->getIterator();
        foreach ($entitiesArray as $entityArray) {
            $fieldsEntity = $entityArray->getFields();
            $cellDatas = [];
            foreach ($fieldsEntity as $fieldEntity) {
                $cellDatas[] = WriterEntityFactory::createCell($fieldEntity->getFormattedValue());
            }
            $singleRowData = WriterEntityFactory::createRow($cellDatas);
            $writer->addRow($singleRowData);
        }
        $writer->close();
        return new Response(stream_get_contents($h));
    }



    protected function getFieldsExport()
    {
        return FieldCollection::new($this->configureFields(Crud::PAGE_INDEX));
    }
}
