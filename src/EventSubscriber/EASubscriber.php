<?php

namespace App\EventSubscriber;

use App\Entity\WebOrder;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class EASubscriber implements EventSubscriberInterface
{
    private $businessCentralAggregator;

    public function __construct(BusinessCentralAggregator $businessCentralAggregator)
    {
        $this->businessCentralAggregator = $businessCentralAggregator;
    }

    public static function getSubscribedEvents()
    {
        return [
            BeforeCrudActionEvent::class => ['setDisplayContent'],
        ];
    }

    public function setDisplayContent(BeforeCrudActionEvent $event)
    {
        $entity = $event->getAdminContext()->getEntity()->getInstance();

        if (!($entity instanceof WebOrder)) {
            return;
        }

        if (in_array($entity->getStatus(),  [WebOrder::STATE_SYNC_TO_ERP, WebOrder::STATE_INVOICED])) {
            $bcConnector =  $this->businessCentralAggregator->getBusinessCentralConnector($entity->getCompany());
            if ($entity->getStatus() == WebOrder::STATE_SYNC_TO_ERP) {

                $content = $bcConnector->getFullSaleOrderByNumber($entity->getOrderErp());
                if ($content) {
                    $content["salesInvoiceLines"] = $content["salesOrderLines"];
                }
                $entity->orderBCContent = $content;
            } elseif ($entity->getStatus() == WebOrder::STATE_INVOICED) {
                $entity->orderBCContent = $bcConnector->getFullSaleInvoiceByNumber($entity->getInvoiceErp());
            }
        }
    }
} {
}
