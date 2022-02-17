<?php

namespace App\Controller\Admin;

use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\Integrator\IntegratorAggregator;
use Box\Spout\Common\Entity\Style\Color;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
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


    public function export(FilterFactory $filterFactory, AdminContext $context)
    {
        $fields = FieldCollection::new($this->configureFields(Crud::PAGE_INDEX));
        $filters = $filterFactory->create($context->getCrud()->getFiltersConfig(), $fields, $context->getEntity());
        $queryBuilder = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), $fields, $filters);
        $entities = $this->get(EntityFactory::class)->createCollection($context->getEntity(), $queryBuilder->getQuery()->getResult());
        $this->get(EntityFactory::class)->processFieldsForAll($entities, $fields);
        $writer = WriterEntityFactory::createCSVWriter();
        $writer->openToBrowser('export_' . str_replace(' ', '_', $this->getName()) . '_' . date('Ymd-His') . '.csv');
        $h = fopen('php://output', 'r');



        $cellHeaders = [];
        foreach ($fields as $field) {
            $cellHeaders[] = WriterEntityFactory::createCell($field->getLabel());
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
}
