<?php

namespace App\Controller\Order;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Controller\Order\WebOrderCrudController;
use App\Entity\IntegrationChannel;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

class FnacDartyOrderCrudController extends WebOrderCrudController
{
    public function getName(): string
    {
        return "FnacDarty Order";
    }


    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.channel IN (:channels)');
        $qb->setParameter(
            'channels',
            [
                IntegrationChannel::CHANNEL_DARTY_FR,
                IntegrationChannel::CHANNEL_FNAC_ES,
                IntegrationChannel::CHANNEL_FNAC_FR,
            ]
        );
        return $qb;
    }


    public function getCompanies()
    {
        return  [
            BusinessCentralConnector::KP_FRANCE => BusinessCentralConnector::KP_FRANCE,
            BusinessCentralConnector::GADGET_IBERIA => BusinessCentralConnector::GADGET_IBERIA,
        ];
    }


    public function getChannels()
    {
        return  [
            IntegrationChannel::CHANNEL_DARTY_FR => IntegrationChannel::CHANNEL_DARTY_FR,
            IntegrationChannel::CHANNEL_FNAC_ES => IntegrationChannel::CHANNEL_FNAC_ES,
            IntegrationChannel::CHANNEL_FNAC_FR => IntegrationChannel::CHANNEL_FNAC_FR,
        ];
    }


    public function getMarketplaces()
    {
        return [
            'Darty FR' => "Darty FR",
            'Fnac ES' => "Fnac ES",
            'Fnac FR' => "Fnac FR",
        ];
    }
}
