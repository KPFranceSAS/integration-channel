<?php

namespace App\EventSubscriber;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\Entity\IntegrationChannel;
use App\Entity\WebOrder;
use App\Service\Amazon\History\AmzHistoryAggregator;
use App\Service\Carriers\TrackingAggregator;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AmazonEventSubscriber implements EventSubscriberInterface
{
    private $businessCentralAggregator;

    private $amzHistoryAggregator;

    private $trackingAggregator;

    public function __construct(
        BusinessCentralAggregator $businessCentralAggregator,
        AmzHistoryAggregator $amzHistoryAggregator,
        TrackingAggregator $trackingAggregator
    ) {
        $this->businessCentralAggregator = $businessCentralAggregator;
        $this->amzHistoryAggregator = $amzHistoryAggregator;
        $this->trackingAggregator = $trackingAggregator;
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

    

        if (in_array($entity->getStatus(), [WebOrder::STATE_SYNC_TO_ERP, WebOrder::STATE_INVOICED, WebOrder::STATE_COMPLETE])) {
            try {
                $bcConnector =  $this->businessCentralAggregator->getBusinessCentralConnector($entity->getCompany());
                if ($entity->getStatus() == WebOrder::STATE_SYNC_TO_ERP) {
                    $content = $bcConnector->getFullSaleOrderByNumber($entity->getOrderErp());
                    if ($content) {
                        $content["salesInvoiceLines"] = $content["salesOrderLines"];
                    } else {
                        $content = $bcConnector->getSaleInvoiceByOrderNumber($entity->getOrderErp());
                    }
                    $entity->orderBCContent = $content;
                } elseif (in_array($entity->getStatus(), [WebOrder::STATE_INVOICED, WebOrder::STATE_COMPLETE])) {
                    $entity->orderBCContent = $bcConnector->getFullSaleInvoiceByNumber($entity->getInvoiceErp());
                }
            } catch (Exception $e) {
            }
        }


        if ($entity->getTrackingCode()) {
            try {
                if(is_array($entity->orderBCContent) && array_key_exists('shippingPostalAddress', $entity->orderBCContent) ){
                    $zipCode = $entity->orderBCContent['shippingPostalAddress']['postalCode'];
                } else {
                    $zipCode = null;
                }
                 $entity->deliverySteps = $this->trackingAggregator->getFormattedSteps($entity->getCarrierService(),$entity->getTrackingCode(), $zipCode );
            } catch (Exception $e) {
            }
        }





        if (in_array($entity->getChannel(), [IntegrationChannel::CHANNEL_CHANNELADVISOR])) {
            $entity->amzEvents = $this->amzHistoryAggregator->getAllEventsOrder($entity->getExternalNumber());
        }
    }
} {
}
