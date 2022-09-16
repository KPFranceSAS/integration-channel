<?php

namespace App\Controller\Admin;

use App\Helper\Utils\StringUtils;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\CSV\Writer;
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
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

abstract class AdminCrudController extends AbstractCrudController
{
    protected $adminUrlGenerator;

    protected $entityRepository;

    public function __construct(AdminUrlGenerator $adminUrlGenerator, EntityRepository $entityRepository)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->entityRepository = $entityRepository;
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
            ->setDateFormat('yyyy-MM-dd')
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



    public function export(
        FilterFactory $filterFactory,
        AdminContext $context,
        EntityFactory $entityFactory,
        ParameterBagInterface $params,
        LoggerInterface $logger
    ) {
        $directory = $params->get('kernel.project_dir') . '/var/export/';
        $fileName = u('Export_' . $this->getName() . '_' . date('Ymd_His'))->snake() . '.csv';
        $fields = $this->getFieldsExport();
        $writer = $this->createWriter($fields, $directory . $fileName);

        $filters = $filterFactory->create($context->getCrud()->getFiltersConfig(), $fields, $context->getEntity());
        $queryBuilder = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), $fields, $filters);
        $pageSize = 1000;
        $currentPage = 1;
        $query = $queryBuilder
            ->setFirstResult(0)
            ->setMaxResults(null)
            ->getQuery();

        $batchs = [];
        foreach ($query->toIterable() as $element) {
            $batchs[] = $element;
            $currentPage++;
            if (($currentPage % $pageSize) === 0) {
                $logger->info("Exported  $currentPage ");
                $entities = $entityFactory->createCollection($context->getEntity(), $batchs);
                $entityFactory->processFieldsForAll($entities, $fields);

                foreach ($entities->getIterator() as $entityArray) {
                    $this->addDataToWriter($writer, $entityArray);
                }
                foreach ($batchs as $batch) {
                    $this->container->get('doctrine')->getManager()->detach($batch);
                }
                unset($batchs);
                unset($entities);
                $batchs = [];
                $this->container->get('doctrine')->getManager()->clear();
            }
        }

        $logger->info("Exported  $currentPage ");
        $entities = $entityFactory->createCollection($context->getEntity(), $batchs);
        $entityFactory->processFieldsForAll($entities, $fields);

        foreach ($entities->getIterator() as $entityArray) {
            $this->addDataToWriter($writer, $entityArray);
        }
        foreach ($batchs as $batch) {
            $this->container->get('doctrine')->getManager()->detach($batch);
        }
        unset($batchs);
        unset($entities);
        $this->container->get('doctrine')->getManager()->clear();
        $writer->close();
        $logger->info('Finish ');

        $response = new BinaryFileResponse($directory . $fileName);
        $response->headers->set('Content-Type', 'text/csv');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );
        return $response;
    }



    protected function processesBatch(array $batch, Writer $writer)
    {
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



    protected function createWriter(FieldCollection $fields, string $filePath): Writer
    {
        $writer = WriterEntityFactory::createCSVWriter();
        $writer->setFieldDelimiter(';');
        $writer->openToFile($filePath);
        $cellHeaders = [];
        foreach ($fields as $field) {
            $label = strlen($field->getLabel()) > 0
                ? $field->getLabel()
                : StringUtils::humanizeString($field->getProperty());
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
