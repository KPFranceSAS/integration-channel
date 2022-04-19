<?php

namespace App\Service\Amazon;

use AmazonPHP\SellingPartner\Model\Finances\AdjustmentEvent;
use AmazonPHP\SellingPartner\Model\Finances\ChargeComponent;
use AmazonPHP\SellingPartner\Model\Finances\CouponPaymentEvent;
use AmazonPHP\SellingPartner\Model\Finances\DebtRecoveryEvent;
use AmazonPHP\SellingPartner\Model\Finances\FeeComponent;
use AmazonPHP\SellingPartner\Model\Finances\FinancialEventGroup;
use AmazonPHP\SellingPartner\Model\Finances\NetworkComminglingTransactionEvent;
use AmazonPHP\SellingPartner\Model\Finances\ProductAdsPaymentEvent;
use AmazonPHP\SellingPartner\Model\Finances\RetrochargeEvent;
use AmazonPHP\SellingPartner\Model\Finances\ServiceFeeEvent;
use AmazonPHP\SellingPartner\Model\Finances\ShipmentEvent;
use AmazonPHP\SellingPartner\Model\Finances\ShipmentItem;
use App\Entity\AmazonFinancialEvent;
use App\Entity\AmazonFinancialEventGroup;
use App\Entity\AmazonOrder;
use App\Entity\AmazonReimbursement;
use App\Entity\AmazonReturn;
use App\Entity\Product;
use App\Entity\ProductCorrelation;
use App\Helper\Utils\ExchangeRateCalculator;
use App\Service\Amazon\AmzApi;
use App\Service\MailService;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
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
        $amazonFinancialEvents = $this->manager->getRepository(AmazonFinancialEvent::class)->findBy(['amazonOrderId' => $orderNumber]);
        $amazonReturnsEvents = $this->manager->getRepository(AmazonReturn::class)->findBy(['amazonOrderId' => $orderNumber]);
        $amazonReimbursementEvents = $this->manager->getRepository(AmazonReimbursement::class)->findBy(['amazonOrderId' => $orderNumber]);
    }


    private function addAmazonFinancialEvent($orderNumber)
    {
        $amazonFinancialEvents = $this->manager->getRepository(AmazonFinancialEvent::class)->findBy(['amazonOrderId' => $orderNumber]);
        foreach ($amazonFinancialEvents as $amazonFinancialEvent) {
        
        }
    }
}
