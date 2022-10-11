<?php

namespace App\EventSubscriber;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\Entity\IntegrationChannel;
use App\Entity\WebOrder;
use App\Service\Amazon\History\AmzHistoryAggregator;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AmazonEventSubscriber implements EventSubscriberInterface
{
    private $businessCentralAggregator;

    private $amzHistoryAggregator;

    public function __construct(
        BusinessCentralAggregator $businessCentralAggregator,
        AmzHistoryAggregator $amzHistoryAggregator
    ) {
        $this->businessCentralAggregator = $businessCentralAggregator;
        $this->amzHistoryAggregator = $amzHistoryAggregator;
    }

    public static function getSubscribedEvents(): array
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

        if (in_array($entity->getStatus(), [WebOrder::STATE_SYNC_TO_ERP, WebOrder::STATE_INVOICED])) {
            $bcConnector =  $this->businessCentralAggregator->getBusinessCentralConnector($entity->getCompany());
            if ($entity->getStatus() == WebOrder::STATE_SYNC_TO_ERP) {
                $content = $bcConnector->getFullSaleOrderByNumber($entity->getOrderErp());
                if ($content) {
                    $content["salesInvoiceLines"] = $content["salesOrderLines"];
                } else {
                    $content = $bcConnector->getSaleInvoiceByOrderNumber($entity->getOrderErp());
                }
                $entity->orderBCContent = $content;
            } elseif ($entity->getStatus() == WebOrder::STATE_INVOICED) {
                $entity->orderBCContent = $bcConnector->getFullSaleInvoiceByNumber($entity->getInvoiceErp());
            }
        }


        if (in_array($entity->getChannel(), [IntegrationChannel::CHANNEL_CHANNELADVISOR])) {
            $entity->amzEvents = $this->amzHistoryAggregator->getAllEventsOrder($entity->getExternalNumber());
        }
    }
} {
}
