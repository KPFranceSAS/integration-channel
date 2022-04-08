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

    protected $dateNow;

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
        $this->logger->info('------------Request financial group---------------');
        $financialGroups = $this->amzApi->getAllFinancials($startDate, $startEnd);
        $this->logger->info('------------Manages with ' . count($financialGroups) . ' groups---------------');
        foreach ($financialGroups as $financialGroup) {

            $this->manageFinancialEventGroup($financialGroup);
        }
    }



    public function manageFinancialEventGroup(FinancialEventGroup $financialEventGroup)
    {
        $this->logger->info('------------' . $financialEventGroup->getFinancialEventGroupId() . '---------------');
        if ($this->checkIfWeImport($financialEventGroup)) {
            sleep(10);
            $financialGroupFormate = $this->convertFinancialEventGroup($financialEventGroup);
            $this->manager->persist($financialGroupFormate);
            $this->dateNow = clone ($financialGroupFormate->getStartDate());
            $financialEvents = $this->getAllFinancialEventsByGroup($financialGroupFormate->getFinancialEventId());
            foreach ($financialEvents as $financialEvent) {
                $financialGroupFormate->addAmazonFinancialEvent($financialEvent);
                $this->manager->persist($financialEvent);
            }
            $this->defineMarketplace($financialGroupFormate);
        }

        $this->manager->flush();
        $this->manager->clear();
        $this->logger->info('---------------------------');
        $this->dateNow = null;
    }


    protected function defineMarketplace(AmazonFinancialEventGroup $amazonFinancialEventGroup)
    {
        foreach ($amazonFinancialEventGroup->getAmazonFinancialEvents() as $amazonFinancialEvent) {
            if ($amazonFinancialEvent->getTransactionType() == "ShipmentEvent") {
                $webOrder = $this->manager->getRepository(AmazonOrder::class)->findOneBy(['amazonOrderId' => $amazonFinancialEvent->getAmazonOrderId()]);
                if ($webOrder && $webOrder->getSalesChannel()) {
                    $amazonFinancialEventGroup->setMarketplace($webOrder->getSalesChannel());
                    return;
                }
            }
        }
    }



    protected function checkIfWeImport(FinancialEventGroup $financialEventGroup): bool
    {
        $financialEventGroupDb = $this->manager->getRepository(AmazonFinancialEventGroup::class)->findOneBy(['financialEventId' => $financialEventGroup->getFinancialEventGroupId()]);

        if (!$financialEventGroupDb) {
            $this->logger->info('Never imported');
            return true;
        }

        if ($financialEventGroup->getProcessingStatus() == 'Open') {
            $this->logger->info('Still opened');
            $this->removeAmazonFinancialEventGroup($financialEventGroupDb);
            return true;
        }

        if ($financialEventGroupDb->getFundTransfertStatus() != $financialEventGroup->getFundTransferStatus() && $financialEventGroupDb->getFundTransfertStatus() != 'Succeeded') {
            $this->logger->info('Fund transfer change ' . $financialEventGroupDb->getFundTransfertStatus() . ' >>>' . $financialEventGroup->getFundTransferStatus());
            $this->setupAmountProperties($financialEventGroup, $financialEventGroupDb);

            return false;
        }

        $this->logger->info('No need to import again');

        return false;
    }




    protected function removeAmazonFinancialEventGroup(AmazonFinancialEventGroup $removeFinancialEvent)
    {
        $this->logger->info('Start delete');
        $this->manager->remove($removeFinancialEvent);
        $this->manager->flush();
        $this->manager->clear();
        $this->logger->info('Delete completed');
    }



    public function getAllFinancialEventsByGroup(string $groupEventId): array
    {
        $financialEvents = $this->amzApi->getFinancialEventsInGroup($groupEventId);
        $financialEventFormates = $this->formateFinancialEvents($financialEvents);
        $this->dateNow = $this->dateNow ? $this->dateNow : new DateTime('now');
        $financialEventTotals = [];

        foreach ($this->getFinancialTypes() as $financialType) {
            $this->logger->info('---------------------------');
            $this->logger->info('Nb Events ' . $financialType . ' >>> ' . count($financialEventFormates[$financialType]));
            $events = [];
            foreach ($financialEventFormates[$financialType] as $financialEvent) {
                try {
                    $events = array_merge($events, $this->{"convert" . $financialType}($financialEvent));
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
            $this->logger->info('After transformation Events exploded ' . $financialType . ' >>> ' . count($events));
            $financialEventTotals  = array_merge($financialEventTotals, $events);
        }
        $this->logger->info('---------------------------');
        $this->logger->info('>>>>>>> Nb amaz financial events :' . count($financialEventTotals));
        return $financialEventTotals;
    }


    protected function convertShipmentEventList(ShipmentEvent $financialEvent): array
    {
        return  $this->convertShipmentEvent($financialEvent, 'ShipmentEvent');
    }


    protected function convertRefundEventList(ShipmentEvent $financialEvent): array
    {
        return $this->convertShipmentEvent($financialEvent, 'RefundEvent');
    }


    protected function convertChargebackEventList(ShipmentEvent $financialEvent): array
    {
        return $this->convertShipmentEvent($financialEvent, 'ChargebackEvent');
    }


    protected function convertGuaranteeClaimEventList(ShipmentEvent $financialEvent): array
    {
        return $this->convertShipmentEvent($financialEvent, 'GuaranteeClaimEvent');
    }


    protected function convertDebtRecoveryEventList(DebtRecoveryEvent $financialEvent): array
    {
        $amzFinancialEvent = new AmazonFinancialEvent();
        $amzFinancialEvent->setTransactionType($financialEvent->getModelName());
        $amzFinancialEvent->setPostedDate($this->dateNow);
        $amzFinancialEvent->setAmountType($financialEvent->getDebtRecoveryType());
        $convertedAmount = $this->calculator->getConvertedAmountDate($financialEvent->getRecoveryAmount()->getCurrencyAmount(), $financialEvent->getRecoveryAmount()->getCurrencyCode(), $amzFinancialEvent->getPostedDate());
        $amzFinancialEvent->setAmount($convertedAmount);
        $amzFinancialEvent->setAmountCurrency($financialEvent->getRecoveryAmount()->getCurrencyAmount());

        return [$amzFinancialEvent];
    }



    protected function convertCouponPaymentEventList(CouponPaymentEvent $financialEvent): array
    {
        $financialEvents = [];
        if ($financialEvent->getFeeComponent() && $financialEvent->getFeeComponent()->getFeeAmount()->getCurrencyAmount() != 0) {
            $amzFinancialEvent = new AmazonFinancialEvent();
            $amzFinancialEvent->setTransactionType($financialEvent->getModelName());
            $amzFinancialEvent->setPostedDate($financialEvent->getPostedDate());
            $amzFinancialEvent->setAmountDescription(substr(html_entity_decode($financialEvent->getSellerCouponDescription()), 0, 255));
            $this->addInfoFeeComponent($amzFinancialEvent, $financialEvent->getFeeComponent());
            $financialEvents[] = $amzFinancialEvent;
        }

        if ($financialEvent->getChargeComponent() && $financialEvent->getChargeComponent()->getChargeAmount()->getCurrencyAmount() != 0) {
            $amzFinancialEvent = new AmazonFinancialEvent();
            $amzFinancialEvent->setTransactionType($financialEvent->getModelName());
            $amzFinancialEvent->setPostedDate($financialEvent->getPostedDate());
            $amzFinancialEvent->setAmountDescription(substr(html_entity_decode($financialEvent->getSellerCouponDescription()), 0, 255));
            $this->addInfoChargeComponent($amzFinancialEvent, $financialEvent->getChargeComponent());
            $financialEvents[] = $amzFinancialEvent;
        }

        return $financialEvents;
    }



    protected function convertRetrochargeEventList(RetrochargeEvent $financialEvent): array
    {
        $amzFinancialEvent = new AmazonFinancialEvent();
        $amzFinancialEvent->setTransactionType($financialEvent->getModelName());
        $amzFinancialEvent->setPostedDate($financialEvent->getPostedDate());
        $amzFinancialEvent->setAmountDescription($financialEvent->getMarketplaceName());
        $amzFinancialEvent->setAmountType($financialEvent->getRetrochargeEventType());
        $amzFinancialEvent->setAmazonOrderId($financialEvent->getAmazonOrderId());
        $convertedAmount = $this->calculator->getConvertedAmountDate($financialEvent->getBaseTax()->getCurrencyAmount(), $financialEvent->getBaseTax()->getCurrencyCode(), $amzFinancialEvent->getPostedDate());
        $amzFinancialEvent->setAmount($convertedAmount);
        $amzFinancialEvent->setAmountCurrency($financialEvent->getBaseTax()->getCurrencyAmount());
        return [$amzFinancialEvent];
    }


    protected function convertShipmentEvent(ShipmentEvent $financialEvent, string $typeEvent): array
    {
        $financialEvents = [];

        $itemChargeCategories = [
            "OrderChargeList",
            "OrderChargeAdjustmentList",
            "ShipmentFeeList",
            "ShipmentFeeAdjustmentList",
            "ShipmentItemList",
            "ShipmentItemAdjustmentList",
            "OrderFeeList",
            "OrderFeeAdjustmentList",
        ];
        foreach ($itemChargeCategories as $itemChargeCategory) {
            $charges = $financialEvent->{'get' . $itemChargeCategory}();
            if ($charges) {
                foreach ($charges as $shipmentItem) {
                    $financialEvents = array_merge($financialEvents, $this->convertShipmentItem($financialEvent, $shipmentItem, $typeEvent));
                }
            }
        }

        return $financialEvents;
    }


    protected function convertProductAdsPaymentEventList(ProductAdsPaymentEvent $financialEvent): array
    {

        $amzFinancialEvent = new AmazonFinancialEvent();
        $amzFinancialEvent->setTransactionType($financialEvent->getModelName());
        $amzFinancialEvent->setPostedDate($financialEvent->getPostedDate());
        $amzFinancialEvent->setAmazonOrderId($financialEvent->getInvoiceId());
        $amzFinancialEvent->setSellerOrderId($financialEvent->getInvoiceId());
        $convertedAmount = $this->calculator->getConvertedAmountDate($financialEvent->getTransactionValue()->getCurrencyAmount(), $financialEvent->getTransactionValue()->getCurrencyCode(), $amzFinancialEvent->getPostedDate());
        $amzFinancialEvent->setAmount($convertedAmount);
        $amzFinancialEvent->setAmountCurrency($financialEvent->getTransactionValue()->getCurrencyAmount());

        return [$amzFinancialEvent];
    }

    protected function convertAdjustmentEventList(AdjustmentEvent $financialEvent): array
    {
        $financialEvents = [];
        if ($financialEvent->getAdjustmentItemList()) {
            foreach ($financialEvent->getAdjustmentItemList() as $adjustementCharge) {
                $amzFinancialEvent = new AmazonFinancialEvent();
                $amzFinancialEvent->setTransactionType($financialEvent->getModelName());
                $amzFinancialEvent->setQtyPurchased($adjustementCharge->getQuantity());

                if ($adjustementCharge->getTotalAmount()->getCurrencyAmount() != 0) {
                    $amzFinancialEvent->setPostedDate($financialEvent->getPostedDate());
                    $amzFinancialEvent->setAmountDescription($financialEvent->getAdjustmentType());
                    $convertedAmount = $this->calculator->getConvertedAmountDate($adjustementCharge->getTotalAmount()->getCurrencyAmount(), $adjustementCharge->getTotalAmount()->getCurrencyCode(), $amzFinancialEvent->getPostedDate());
                    $amzFinancialEvent->setAmount($convertedAmount);
                    $amzFinancialEvent->setAmountCurrency($adjustementCharge->getTotalAmount()->getCurrencyAmount());
                    $amzFinancialEvent->setSku($adjustementCharge->getSellerSku());
                    $amzFinancialEvent->setProduct($this->getProductBySku($adjustementCharge->getSellerSku()));
                    $financialEvents[] = $amzFinancialEvent;
                }
            }
        } else {
            $amzFinancialEvent = new AmazonFinancialEvent();
            $amzFinancialEvent->setTransactionType($financialEvent->getModelName());
            $amzFinancialEvent->setPostedDate($financialEvent->getPostedDate());
            $amzFinancialEvent->setAmountDescription($financialEvent->getAdjustmentType());
            $convertedAmount = $this->calculator->getConvertedAmountDate($financialEvent->getAdjustmentAmount()->getCurrencyAmount(), $financialEvent->getAdjustmentAmount()->getCurrencyCode(), $amzFinancialEvent->getPostedDate());
            $amzFinancialEvent->setAmount($convertedAmount);
            $amzFinancialEvent->setAmountCurrency($financialEvent->getAdjustmentAmount()->getCurrencyAmount());
            $financialEvents[] = $amzFinancialEvent;
        }

        return $financialEvents;
    }


    protected function convertNetworkComminglingTransactionEventList(NetworkComminglingTransactionEvent $financialEvent): array
    {
        $amzFinancialEvent = new AmazonFinancialEvent();
        $amzFinancialEvent->setTransactionType($financialEvent->getModelName());
        $amzFinancialEvent->setPostedDate($financialEvent->getPostedDate());
        $amzFinancialEvent->setAmountDescription($financialEvent->getSwapReason());
        $amzFinancialEvent->setAmountType($financialEvent->getTransactionType());
        $amzFinancialEvent->setAmazonOrderId($financialEvent->getNetCoTransactionId());
        $amzFinancialEvent->setSellerOrderId($financialEvent->getMarketplaceId());
        $convertedAmount = $this->calculator->getConvertedAmountDate($financialEvent->getTaxAmount()->getCurrencyAmount(), $financialEvent->getTaxAmount()->getCurrencyCode(), $amzFinancialEvent->getPostedDate());
        $amzFinancialEvent->setAmount($convertedAmount);
        $amzFinancialEvent->setAmountCurrency($financialEvent->getTaxAmount()->getCurrencyAmount());
        $amzFinancialEvent->setSku($financialEvent->getAsin());
        $amzFinancialEvent->setProduct($this->getProductByAsin($financialEvent->getAsin()));

        return [$amzFinancialEvent];
    }

    protected function convertServiceFeeEventList(ServiceFeeEvent $financialEvent): array
    {
        $financialEvents = [];
        dump($financialEvent);

        foreach ($financialEvent->getFeeList() as $feeCharge) {
            $amzFinancialEvent = new AmazonFinancialEvent();
            $amzFinancialEvent->setTransactionType($financialEvent->getModelName());
            $amzFinancialEvent->setPostedDate($this->dateNow);
            $amzFinancialEvent->setAmazonOrderId($financialEvent->getAmazonOrderId());
            $amzFinancialEvent->setSellerOrderId($financialEvent->getAmazonOrderId());
            $this->addInfoFeeComponent($amzFinancialEvent, $feeCharge);
            $financialEvents[] = $amzFinancialEvent;
        }
        return $financialEvents;
    }


    protected function convertShipmentItem(ShipmentEvent $shipmentEvent, ShipmentItem $shipmentItem, string $typeEvent): array
    {
        $product = $this->getProductBySku($shipmentItem->getSellerSku());

        $financialEvents = [];
        $itemChargeCategories = ["ItemChargeList", "ItemChargeAdjustmentList", "ItemFeeList", "ItemFeeAdjustmentList"];
        foreach ($itemChargeCategories as $itemChargeCategory) {
            $charges = $shipmentItem->{'get' . $itemChargeCategory}();
            if ($charges) {
                foreach ($charges as $itemCharge) {
                    $amzFinancialEvent = $this->createShipmentEvent($shipmentEvent, $shipmentItem, $typeEvent, $product);
                    $amzFinancialEvent->setAmountType($itemChargeCategory);
                    if ($itemCharge->getModelName() == 'ChargeComponent') {
                        $this->addInfoChargeComponent($amzFinancialEvent, $itemCharge);
                    } elseif ($itemCharge->getModelName() == 'FeeComponent') {
                        $this->addInfoFeeComponent($amzFinancialEvent, $itemCharge);
                    }
                    if ($amzFinancialEvent->getAmount() != 0) {
                        $financialEvents[] = $amzFinancialEvent;
                    }
                }
            }
        }


        $chargesTva = $shipmentItem->getItemTaxWithheldList();
        if ($chargesTva) {
            foreach ($chargesTva as $itemCharge) {
                foreach ($itemCharge->getTaxesWithheld() as $taxeItem) {
                    $amzFinancialEvent = $this->createShipmentEvent($shipmentEvent, $shipmentItem, $typeEvent, $product);
                    $amzFinancialEvent->setAmountType($itemCharge->getTaxCollectionModel());
                    $this->addInfoChargeComponent($amzFinancialEvent, $taxeItem);
                    if ($amzFinancialEvent->getAmount() != 0) {
                        $financialEvents[] = $amzFinancialEvent;
                    }
                }
            }
        }


        $itemChargeCategories = ["PromotionAdjustmentList", "PromotionList"];
        foreach ($itemChargeCategories as $itemChargeCategory) {
            $chargesPromotion = $shipmentItem->{'get' . $itemChargeCategory}();
            if ($chargesPromotion) {
                foreach ($chargesPromotion as $chargePromotion) {
                    $amzFinancialEvent = $this->createShipmentEvent($shipmentEvent, $shipmentItem, $typeEvent, $product);
                    $amzFinancialEvent->setAmountType($itemChargeCategory);
                    if ($chargePromotion->getPromotionAmount()->getCurrencyAmount() != 0) {
                        $amzFinancialEvent->setAmountDescription($chargePromotion->getPromotionType());
                        $amzFinancialEvent->setPromotionId($chargePromotion->getPromotionId());
                        $convertedAmount = $this->calculator->getConvertedAmountDate($chargePromotion->getPromotionAmount()->getCurrencyAmount(), $chargePromotion->getPromotionAmount()->getCurrencyCode(), $amzFinancialEvent->getPostedDate());
                        $amzFinancialEvent->setAmount($convertedAmount);
                        $amzFinancialEvent->setAmountCurrency($chargePromotion->getPromotionAmount()->getCurrencyAmount());
                        $financialEvents[] = $amzFinancialEvent;
                    }
                }
            }
        }
        return $financialEvents;
    }


    protected function createShipmentEvent(ShipmentEvent $shipmentEvent, ShipmentItem $shipmentItem, string  $typeEvent, ?Product $product): AmazonFinancialEvent
    {
        $amzFinancialEvent = new AmazonFinancialEvent();
        $amzFinancialEvent->setTransactionType($typeEvent);
        $amzFinancialEvent->setPostedDate($shipmentEvent->getPostedDate());
        $amzFinancialEvent->setAmazonOrderId($shipmentEvent->getAmazonOrderId());
        $amzFinancialEvent->setSellerOrderId($shipmentEvent->getSellerOrderId());
        $amzFinancialEvent->setMarketplaceName($shipmentEvent->getMarketplaceName());
        $amzFinancialEvent->setOrderItemCode($shipmentItem->getOrderItemId());
        $amzFinancialEvent->setQtyPurchased($shipmentItem->getQuantityShipped());
        $amzFinancialEvent->setAdjustmentId($shipmentItem->getOrderAdjustmentItemId());
        $amzFinancialEvent->setSku($shipmentItem->getSellerSku());
        $amzFinancialEvent->setProduct($product);
        return $amzFinancialEvent;
    }


    protected function addInfoChargeComponent(AmazonFinancialEvent $amazonFinancialEvent, ChargeComponent $chargeComponent)
    {
        $amazonFinancialEvent->setAmountDescription($chargeComponent->getChargeType());
        $convertedAmount = $this->calculator->getConvertedAmountDate($chargeComponent->getChargeAmount()->getCurrencyAmount(), $chargeComponent->getChargeAmount()->getCurrencyCode(), $amazonFinancialEvent->getPostedDate());
        $amazonFinancialEvent->setAmount($convertedAmount);
        $amazonFinancialEvent->setAmountCurrency($chargeComponent->getChargeAmount()->getCurrencyAmount());
    }


    protected function addInfoFeeComponent(AmazonFinancialEvent $amazonFinancialEvent, FeeComponent $feeComponent)
    {
        $amazonFinancialEvent->setAmountDescription($feeComponent->getFeeType());
        $convertedAmount = $this->calculator->getConvertedAmountDate($feeComponent->getFeeAmount()->getCurrencyAmount(), $feeComponent->getFeeAmount()->getCurrencyCode(), $amazonFinancialEvent->getPostedDate());
        $amazonFinancialEvent->setAmount($convertedAmount);
        $amazonFinancialEvent->setAmountCurrency($feeComponent->getFeeAmount()->getCurrencyAmount());
    }



    protected function getProductBySku($sku)
    {
        $skuSanitized = strtoupper($sku);
        if (strlen($skuSanitized) > 0) {
            $productCorrelation = $this->manager->getRepository(ProductCorrelation::class)->findOneBy(['skuUsed' => $skuSanitized]);
            $sku = $productCorrelation ? $productCorrelation->getSkuErp() : $skuSanitized;

            $product = $this->manager->getRepository(Product::class)->findOneBy([
                "sku" => $sku
            ]);
            return $product;
        } else {
            return null;
        }
    }



    protected function getProductByAsin($asin)
    {
        $skuSanitized = strtoupper($asin);
        if (strlen($skuSanitized) > 0) {
            $product = $this->manager->getRepository(Product::class)->findOneBy([
                "asin" => $asin
            ]);
            return $product;
        } else {
            return null;
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

        $amzFinancialEventGroup->setStartDate($financialEventGroup->getFinancialEventGroupStart());
        $amzFinancialEventGroup->setEndDate($financialEventGroup->getFinancialEventGroupEnd());
        $this->setupAmountProperties($financialEventGroup, $amzFinancialEventGroup);
        return $amzFinancialEventGroup;
    }



    protected function setupAmountProperties(FinancialEventGroup $financialEventGroup, AmazonFinancialEventGroup $amzFinancialEventGroup)
    {
        $amzFinancialEventGroup->setFundTransfertStatus($financialEventGroup->getFundTransferStatus());
        $amzFinancialEventGroup->setFundTransferDate($financialEventGroup->getFundTransferDate());
        $amzFinancialEventGroup->setTraceIdentfier($financialEventGroup->getTraceId());
        $attributes = ['BeginningBalance', 'OriginalTotal', 'ConvertedTotal'];


        foreach ($attributes as $attribute) {
            $value = $financialEventGroup->{'get' . $attribute}();
            if ($value) {
                $valueFormate = $value->getCurrencyAmount();

                $dateCalcul = $amzFinancialEventGroup->getEndDate() ? $amzFinancialEventGroup->getEndDate() : $amzFinancialEventGroup->getStartDate();
                $valueFormateCurrency = round($this->calculator->getConvertedAmountDate($valueFormate, $value->getCurrencyCode(), $dateCalcul), 2);
                $amzFinancialEventGroup->{'set' . $attribute . 'Currency'}($valueFormate);
                $amzFinancialEventGroup->{'set' . $attribute}($valueFormateCurrency);

                if (!$amzFinancialEventGroup->getCurrencyCode()) {
                    $amzFinancialEventGroup->setCurrencyCode($value->getCurrencyCode());
                }
            }
        }
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
