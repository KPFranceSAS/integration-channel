<?php

namespace App\Service\Amazon\History;

use App\Entity\AmazonFinancialEvent;
use App\Entity\AmazonOrder;
use App\Entity\AmazonReimbursement;
use App\Entity\AmazonReturn;
use App\Service\Amazon\History\AmzHistoryEvent;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class AmzHistoryAggregator
{
    protected $manager;

    protected $logger;


    public function __construct(LoggerInterface $logger, ManagerRegistry $manager)
    {
        $this->logger = $logger;
        $this->manager = $manager->getManager();
    }

    private $events;

    public function getAllEventsOrder($orderNumber)
    {
        $this->events = [];
        $this->addAmazonOrderEvents($orderNumber);
        $this->addAmazonReturnEvents($orderNumber);
        $this->addAmazonReimbursementEvents($orderNumber);
        $this->addAmazonFinancialEvents($orderNumber);
        ksort($this->events);
        return $this->events;
    }


    private function addAmazonOrderEvents($orderNumber)
    {
        /** @var array[\App\Entity\AmazonOrder] */
        $amazonEvents = $this->manager
                         ->getRepository(AmazonOrder::class)
                         ->findBy(['amazonOrderId' => $orderNumber]);
        foreach ($amazonEvents as $amazonEvent) {
            $amzHistoryEvent = AmzHistoryEvent::createOneFromAmazonOrder($amazonEvent);
            $this->addToEvents($amzHistoryEvent);
        }
    }

    private function addAmazonFinancialEvents($orderNumber)
    {
         /** @var array[\App\Entity\AmazonFinancialEvent] */
        $amazonEvents = $this->manager
                            ->getRepository(AmazonFinancialEvent::class)
                            ->findBy(['amazonOrderId' => $orderNumber]);
        foreach ($amazonEvents as $amazonEvent) {
            $amzHistoryEvent = AmzHistoryEvent::createOneFromAmazonFinancialEvent($amazonEvent);
            $this->addToEvents($amzHistoryEvent);
        }
    }


    private function addAmazonReturnEvents($orderNumber)
    {
        /** @var array[\App\Entity\AmazonReturn] */
        $amazonEvents = $this->manager
                            ->getRepository(AmazonReturn::class)
                            ->findBy(['orderId' => $orderNumber]);
        foreach ($amazonEvents as $amazonEvent) {
            $amzHistoryEvent = AmzHistoryEvent::createOneFromAmazonReturn($amazonEvent);
            $this->addToEvents($amzHistoryEvent);
        }
    }


    private function addAmazonReimbursementEvents($orderNumber)
    {
        /** @var array[\App\Entity\AmazonReimbursement] */
        $amazonEvents = $this->manager
                            ->getRepository(AmazonReimbursement::class)
                            ->findBy(['amazonOrderId' => $orderNumber]);
        foreach ($amazonEvents as $amazonEvent) {
            $amzHistoryEvent = AmzHistoryEvent::createOneFromAmazonReimbursement($amazonEvent);
            $this->addToEvents($amzHistoryEvent);
        }
    }


    private function addToEvents(AmzHistoryEvent $amzHistoryEvent)
    {
        $dateEvent = $amzHistoryEvent->dateEvent->format('Y-m-d');
        if (!array_key_exists($dateEvent, $this->events)) {
            $this->events[$dateEvent] = [
                'events' => [],
                'dateEvent' => $amzHistoryEvent->dateEvent->format('d-m-Y')
            ];
        }
        $this->events[$dateEvent]['events'][] = $amzHistoryEvent;
    }
}
