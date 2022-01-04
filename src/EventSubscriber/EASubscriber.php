<?php

namespace App\EventSubscriber;

use App\Entity\WebOrder;
use App\Service\BusinessCentral\BusinessCentralConnector;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class EASubscriber implements EventSubscriberInterface
{
    private $businessCentralConnector;

    public function __construct(BusinessCentralConnector $businessCentralConnector)
    {
        $this->businessCentralConnector = $businessCentralConnector;
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

        if ($entity->getStatus() == WebOrder::STATE_SYNC_TO_ERP) {
            $content = $this->businessCentralConnector->getFullSaleOrderByNumber($entity->getOrderErp());
            $content["salesInvoiceLines"] = $content["salesOrderLines"];
            $entity->orderBCContent = $content;
        } elseif ($entity->getStatus() == WebOrder::STATE_INVOICED) {
            $entity->orderBCContent = $this->businessCentralConnector->getFullSaleInvoiceByNumber($entity->getInvoiceErp());
        }
    }
} {
}
