<?php

namespace App\Service\Amazon;

use AmazonPHP\SellingPartner\Model\Finances\FinancialEventGroup;
use App\Entity\AmazonFinancialEventGroup;
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

    protected $calculator;

    public function __construct(LoggerInterface $logger, AmzApi $amzApi, ManagerRegistry $manager, MailService $mailer, ExchangeRateCalculator $exchangeRate)
    {
        $this->logger = $logger;
        $this->amzApi = $amzApi;
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->calculator = $exchangeRate;
    }



    public function getAllFinancials($startDate, $startEnd)
    {

        $financialGroups = $this->amzApi->getAllFinancials($startDate, $startEnd);


        foreach ($financialGroups as $financialGroup) {
            $financialGroupFormate = $this->convertFinancialEventGroup($financialGroup);
        }
    }



    public function saveFinancialEvent($groupEventId)
    {
        $financialEvents = $this->amzApi->getFinancialEventsInGroup($groupEventId);
        $financialEventFormates = $this->formateFinancialEvents($financialEvents);


        foreach ($this->getFinancialTypes() as $financialType) {
            $this->logger->info('Nb Events ' . $financialType . ' >>> ' . count($financialEventFormates[$financialType]));
            foreach ($financialEventFormates[$financialType] as $financialEvent) {
            }
        }
    }


    protected function formateFinancialEvents($financialEvents): array
    {
        $financialTypes = $this->getFinancialTypes();
        $allEvents = array_fill_keys($financialTypes, []);
        foreach ($financialEvents as $financialEvent) {
            foreach ($financialTypes as $financialType) {
                $eventsFinancialTypes = $financialEvent->{'get' . $financialType}();
                foreach ($eventsFinancialTypes as $eventsFinancialType) {
                    $allEvents[$financialType][] = $eventsFinancialType;
                }
            }
        }
        return $allEvents;
    }

    protected function convertFinancialEventGroup(FinancialEventGroup $financialEventGroup): AmazonFinancialEventGroup
    {
        $amzFinancialEventGroup = new AmazonFinancialEventGroup();
        $amzFinancialEventGroup->setFinancialEventId($financialEventGroup->getFinancialEventGroupId());
        $amzFinancialEventGroup->setProcessingStatus($financialEventGroup->getProcessingStatus());
        $amzFinancialEventGroup->setFundTransfertStatus($financialEventGroup->getFundTransferStatus());
        $amzFinancialEventGroup->setFundTransferDate($financialEventGroup->getFundTransferDate());
        $amzFinancialEventGroup->setTraceIdentfier($financialEventGroup->getTraceId());
        $amzFinancialEventGroup->setProcessingStatus($financialEventGroup->getProcessingStatus());
        $amzFinancialEventGroup->setStartDate($financialEventGroup->getFinancialEventGroupStart());
        $amzFinancialEventGroup->setEndDate($financialEventGroup->getFinancialEventGroupEnd());
        $attributes = ['BeginningBalance', 'OriginalTotal', 'ConvertedTotal'];


        foreach ($attributes as $attribute) {
            $value = $financialEventGroup->{'get' . $attribute}();
            if ($value) {
                $valueFormate = $value->getCurrencyAmount();
                if ($valueFormate >= 0) {
                    $dateCalcul = $amzFinancialEventGroup->getEndDate() ? $amzFinancialEventGroup->getEndDate() : $amzFinancialEventGroup->getStartDate();
                    $valueFormateCurrency = round($this->calculator->getConvertedAmountDate($valueFormate, $value->getCurrencyCode(), $dateCalcul), 2);
                    $amzFinancialEventGroup->{'set' . $attribute . 'Currency'}($valueFormate);
                    $amzFinancialEventGroup->{'set' . $attribute}($valueFormateCurrency);
                }
                if (!$amzFinancialEventGroup->getCurrencyCode()) {
                    $amzFinancialEventGroup->setCurrencyCode($value->getCurrencyCode());
                }
            }
        }
        return $amzFinancialEventGroup;
    }



















    protected function getFinancialTypes(): array
    {
        return [
            "ShipmentEventList",
            "RefundEventList",
            "GuaranteeClaimEventList",
            "ChargebackEventList",
            "PayWithAmazonEventList",
            "ServiceProviderCreditEventList",
            "RetrochargeEventList",
            "RentalTransactionEventList",
            "ProductAdsPaymentEventList",
            "ServiceFeeEventList",
            "SellerDealPaymentEventList",
            "DebtRecoveryEventList",
            "LoanServicingEventList",
            "AdjustmentEventList",
            "SAFETReimbursementEventList",
            "SellerReviewEnrollmentPaymentEventList",
            "FBALiquidationEventList",
            "CouponPaymentEventList",
            "ImagingServicesFeeEventList",
            "NetworkComminglingTransactionEventList",
            "AffordabilityExpenseEventList",
            "AffordabilityExpenseReversalEventList",
            "TaxWithholdingEventList",
            "RemovalShipmentEventList",
            "RemovalShipmentAdjustmentEventList"
        ];
    }
}
