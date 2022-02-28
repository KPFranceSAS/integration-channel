<?php

namespace App\Service\Amazon;

use App\Entity\Product;
use App\Entity\ProductCorrelation;
use App\Helper\Utils\ExchangeRateCalculator;
use App\Service\Amazon\AmzApi;
use App\Service\MailService;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;


class AmzApiFinancial
{

    protected $mailer;

    protected $manager;

    protected $logger;

    protected $amzApi;

    public function __construct(LoggerInterface $logger, AmzApi $amzApi, ManagerRegistry $manager, MailService $mailer, ExchangeRateCalculator $exchangeRate)
    {
        $this->logger = $logger;
        $this->amzApi = $amzApi;
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->exchangeRate = $exchangeRate;
    }


    private function getAllTypes()
    {
        $financialTypes = ["ShipmentEventList", "RefundEventList", "GuaranteeClaimEventList", "ChargebackEventList", "PayWithAmazonEventList", "ServiceProviderCreditEventList", "RetrochargeEventList", "RentalTransactionEventList", "ProductAdsPaymentEventList", "ServiceFeeEventList", "SellerDealPaymentEventList", "DebtRecoveryEventList", "LoanServicingEventList", "AdjustmentEventList", "SAFETReimbursementEventList", "SellerReviewEnrollmentPaymentEventList", "FBALiquidationEventList", "CouponPaymentEventList", "ImagingServicesFeeEventList", "NetworkComminglingTransactionEventList", "AffordabilityExpenseEventList", "AffordabilityExpenseReversalEventList", "TaxWithholdingEventList", "RemovalShipmentEventList", "RemovalShipmentAdjustmentEventList"];
        return $financialTypes;
    }


    public function saveFinancialEvent($groupEventId)
    {

        $financialEvents = $this->amzApi->getFinancialEventsInGroup($groupEventId);
        foreach ($financialEvents as $financialEvent) {
        }
    }

    /*$financialTypes = ["ShipmentEventList", "RefundEventList", "GuaranteeClaimEventList", "ChargebackEventList", "PayWithAmazonEventList", "ServiceProviderCreditEventList", "RetrochargeEventList", "RentalTransactionEventList", "ProductAdsPaymentEventList", "ServiceFeeEventList", "SellerDealPaymentEventList", "DebtRecoveryEventList", "LoanServicingEventList", "AdjustmentEventList", "SAFETReimbursementEventList", "SellerReviewEnrollmentPaymentEventList", "FBALiquidationEventList", "CouponPaymentEventList", "ImagingServicesFeeEventList", "NetworkComminglingTransactionEventList", "AffordabilityExpenseEventList", "AffordabilityExpenseReversalEventList", "TaxWithholdingEventList", "RemovalShipmentEventList", "RemovalShipmentAdjustmentEventList"];
            $financialEvents = $payLoad->getFinancialEvents();
            foreach ($financialTypes as $financialType) {
                $eventsFinancialTypes = $financialEvents->{'get' . $financialType}();
                foreach ($eventsFinancialTypes as $eventsFinancialType) {
                    $allEvents[] = $eventsFinancialType;
                }
            }*/




    public function createReportAndImport(?DateTime $dateTimeStart = null)
    {
    }
}
