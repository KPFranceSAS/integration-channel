<?php

namespace App\Controller\Configuration;

use App\Controller\Admin\AdminCrudController;
use App\Entity\IntegrationChannel;
use App\Entity\OrderLog;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;

class OrderLogCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return OrderLog::class;
    }

    public function getDefautOrder(): array
    {
        return ['logDate' => "DESC"];
    }

    public function getName(): string
    {
        return 'Order error log';
    }



    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        return $actions->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE);
    }




    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('category', 'Category'),
            TextField::new('description', 'Error'),
            TextField::new('orderNumber', 'Order number'),
            TextField::new('marketplace', 'Marketplace'),
            TextField::new('integrationChannel', 'Integration'),
            DateTimeField::new('logDate', "Date")
        ];
    }
    

    public function configureFilters(Filters $filters): Filters
    {
        

        $choiceStatuts = [
            OrderLog::CATEGORY_DELAY_DELIVERY => OrderLog::CATEGORY_DELAY_DELIVERY,
            OrderLog::CATEGORY_DELAY_INVOICE => OrderLog::CATEGORY_DELAY_INVOICE,
            OrderLog::CATEGORY_DELAY_SHIPMENT_CREATION => OrderLog::CATEGORY_DELAY_SHIPMENT_CREATION,
            OrderLog::CATEGORY_ERP => OrderLog::CATEGORY_ERP,
            OrderLog::CATEGORY_LENGTH => OrderLog::CATEGORY_LENGTH,
            OrderLog::CATEGORY_SKU => OrderLog::CATEGORY_SKU,
            OrderLog::CATEGORY_OTHERS => OrderLog::CATEGORY_OTHERS,
            OrderLog::CATEGORY_SYSTEM => OrderLog::CATEGORY_SYSTEM,
        ];


        return $filters
            ->add(ChoiceFilter::new('category')->canSelectMultiple(true)->setChoices($choiceStatuts))
            ->add(DateTimeFilter::new('logDate', "Log date"))
            ->add(DateTimeFilter::new('createdAt', "Created at"))
            ->add(ChoiceFilter::new('integrationChannel', "Integration Channel")->canSelectMultiple(true)->setChoices($this->getChannels()))
            ->add(ChoiceFilter::new('marketplace', "Marketplace")->canSelectMultiple(true)->setChoices($this->getMarketplaces()));
    }



    public function getMarketplaces()
    {
        return [
            'AliExpress' => 'AliExpress',
            'Amazon UK' => 'Amazon UK',
            'Amazon IT'  => "Amazon Seller Central - IT",
            'Amazon DE' => "Amazon Seller Central - DE",
            'Amazon ES' => "Amazon Seller Central - ES",
            'Amazon FR' => "Amazon Seller Central - FR",
            "Fitbitcorporate.kps.tech"=> "Fitbitcorporate.kps.tech",
            'Flashled.es' => 'Flashled.es',
            'Miravia.es' => 'Miravia.es',
            'Minibatt.com' => 'Minibatt.com',
            'Owletbaby.es' => 'Owletbaby.es',
        ];
    }


    public function getChannels()
    {
        $channels = $this->container->get('doctrine')
                    ->getManager()
                    ->getRepository(IntegrationChannel::class)
                    ->findAll();
        $channelArray=[];
        foreach ($channels as $channel) {
            $channelArray[$channel->getCode()] = $channel->getName();
        }
        return $channelArray;
    }
}
