<?php

namespace App\Controller\Admin;

use App\Helper\Utils\StringUtils;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\CSV\Writer;
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
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use function Symfony\Component\String\u;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class AdminCrudController extends AbstractCrudController
{
    protected $adminUrlGenerator;

    public function __construct(AdminUrlGenerator $adminUrlGenerator)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
    }


    public function getName()
    {
        $reflectionClass = new ReflectionClass($this);
        return str_replace('CrudController', '', $reflectionClass->getShortName());
    }


    public function getDefautOrder(): array
    {
        return ['createdAt' => "DESC"];
    }


    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular($this->getName())
            ->setEntityLabelInPlural($this->getName() . 's')
            ->setDateTimeFormat('yyyy-MM-dd HH:mm')
            ->setDefaultSort($this->getDefautOrder())
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
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setIcon('fa fa-plus')->setLabel("Add a new " . $this->getName());
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setIcon('fa fa-pencil')->setLabel(false);
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-trash')->setLabel(false);
            });
    }


    public function export(FilterFactory $filterFactory, AdminContext $context, EntityFactory $entityFactory)
    {
        $fields = $this->getFieldsExport();
        $entityRepository = $this->container->get(EntityRepository::class);

        $response = new StreamedResponse(function () use ($fields, $filterFactory, $context, $entityFactory, $entityRepository) {
            $csv = fopen('php://output', 'w+');

            $cellHeaders = [];
            foreach ($fields as $field) {
                $label = strlen($field->getLabel()) > 0
                        ? $field->getLabel()
                        : StringUtils::humanizeString($field->getProperty());
                $cellHeaders[] = $label;
            }
            fputcsv($csv, $cellHeaders);

            $filters = $filterFactory->create($context->getCrud()->getFiltersConfig(), $fields, $context->getEntity());
            $queryBuilder = $entityRepository->createQueryBuilder(
                $context->getSearch(),
                $context->getEntity(),
                $fields,
                $filters
            );

            $pageSize = 200;
            $currentPage = 1;

            do {
                $firstResult = ($currentPage - 1) * $pageSize;
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
                    $fieldsEntity = $entityArray->getFields();
                    $cellDatas = [];
                    foreach ($fieldsEntity as $fieldEntity) {
                        $cellDatas[] = $fieldEntity->getFormattedValue();
                    }
                    fputcsv($csv, $cellDatas);
                    $this->container->get('doctrine')->getManager()->detach($entityArray);
                }
                $this->container->get('doctrine')->getManager()->clear();
            } while ($currentPage != 0);
                

            fclose($csv);
        });
        $fileName = u('Export ' . $this->getName() . ' ' . date('Ymd His'))->snake() . '.csv';
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }


   



    protected function getFieldsExport(): FieldCollection
    {
        return FieldCollection::new($this->configureFields(Crud::PAGE_INDEX));
    }




    protected function generateChoiceList(array $choices): array
    {
        $choiceList = [];
        foreach ($choices as $choice) {
            $choiceList[$choice] = $choice;
        }
        ksort($choiceList);
        return $choiceList;
    }
}
