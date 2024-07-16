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

class PcComponentesOrderCrudController extends WebOrderCrudController
{
    public function getName(): string
    {
        return "PcComponentes order";
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.channel = :channel');
        $qb->setParameter('channel', IntegrationChannel::CHANNEL_PCCOMPONENTES);
        return $qb;
    }


    public function getCompanies()
    {
        return  [
            BusinessCentralConnector::KIT_PERSONALIZACION_SPORT => BusinessCentralConnector::KIT_PERSONALIZACION_SPORT,
        ];
    }

    public function getChannels()
    {
        return  [
            IntegrationChannel::CHANNEL_PCCOMPONENTES => IntegrationChannel::CHANNEL_PCCOMPONENTES,
        ];
    }


    public function getMarketplaces()
    {
        return [
            'PcComponentes.es' => 'PcComponentes.es',
            'PcComponentes.pt' => 'PcComponentes.pt',
            'PcComponentes.fr' => 'PcComponentes.fr',
            'PcComponentes.it' => 'PcComponentes.it',
            'PcComponentes.de' => 'PcComponentes.de',
        ];
    }
}
