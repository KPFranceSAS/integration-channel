<?php

namespace App\Command\Channels\ChannelAdvisor;

use App\Entity\AmazonOrder;
use App\Entity\WebOrder;
use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\BusinessCentral\Connector\KpFranceConnector;
use Doctrine\Persistence\ManagerRegistry;
use stdClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildHistoricCommand extends Command
{
    protected static $defaultName = 'app:channel-build-historic';
    protected static $defaultDescription = 'Build historical orders';

    public function __construct(KpFranceConnector $saleOrderConnector, ManagerRegistry $manager)
    {
        /** @var \Doctrine\ORM\EntityManagerInterface */
        $this->manager = $manager->getManager();
        $this->bcConnector = $saleOrderConnector;
        parent::__construct();
    }

    private $bcConnector;

    private $manager;


    private $toTransform = [];


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $counter = 0;
       
        $q = $this->manager->createQuery('select a from App\Entity\AmazonOrder a where a.orderStatus = :shipped and a.purchaseDate < :dateCreate and a.itemStatus = :shipped
            and a.amazonOrderId NOT IN (select p.externalNumber from App\Entity\WebOrder p)')
            ->setParameter('dateCreate', '2022-01-01 00:00:00')
            ->setParameter('shipped', 'Shipped');
        foreach ($q->toIterable() as $amzOrder) {
            if ($this->checkIfImport($amzOrder)) {
                $subChannel = $this->matchSubChannel($amzOrder->getSalesChannel());
                if ($subChannel) {
                    $this->toTransform[$amzOrder->getAmazonOrderId()] = $subChannel;
                }
            }
        }
        $this->manager->clear();
        $output->writeln('Start integration ' . count($this->toTransform));

        $progressPar = new ProgressBar($output, count($this->toTransform));
        $progressPar->start();
        $nvCounter = 0;
        foreach ($this->toTransform as $toTransformNumber => $toTransformChannel) {
            if ($this->treatFromChannel($toTransformNumber, $toTransformChannel, $output)) {
                $nvCounter++;
            }


            if ($nvCounter % 20 == 0) {
                $this->manager->flush();
                $this->manager->clear();
            }
            $progressPar->advance();
        }
        $progressPar->finish();
        $this->manager->flush();
        return Command::SUCCESS;
    }




    public function treatFromChannel($amzOrderId, $amzOrderSubChannel, OutputInterface $output)
    {
        $webOrder = new WebOrder();
        $webOrder->setExternalNumber($amzOrderId);
        $webOrder->setStatus(WebOrder::STATE_INVOICED);
        $webOrder->setChannel(WebOrder::CHANNEL_CHANNELADVISOR);
        $webOrder->setSubchannel($amzOrderSubChannel);

        $webOrder->setErpDocument(WebOrder::DOCUMENT_INVOICE);
        $webOrder->setWarehouse(WebOrder::DEPOT_FBA_AMAZON);
        $webOrder->setFulfilledBy(WebOrder::FULFILLED_BY_EXTERNAL);
        $webOrder->setCompany(BusinessCentralConnector::KP_FRANCE);
        $webOrder->addLog('Build from Amz file');
        $orderApi = $this->recreateChannelObject($amzOrderId, $webOrder);
        try {
            $invoice = $this->bcConnector->getSaleInvoiceByExternalNumber($amzOrderId);
            if ($invoice) {
                $output->writeln('Retrieved data from business central');
                $webOrder->addLog('Retrieved data from business central');
                $webOrder->setOrderErp($invoice['orderNumber']);
                $orderApi->ShippingLastName = $invoice['shipToName'];
                $orderApi->ShippingAddressLine1 = $invoice['shippingPostalAddress']["street"];
                ;
                $orderApi->ShippingCity = $invoice['shippingPostalAddress']["city"];
                $orderApi->ShippingStateOrProvince =  $invoice['shippingPostalAddress']["state"];
                $orderApi->ShippingStateOrProvinceName = $invoice['shippingPostalAddress']["state"];
                $orderApi->ShippingPostalCode = $invoice['shippingPostalAddress']["postalCode"];
                $orderApi->ShippingCountry = $invoice['shippingPostalAddress']["countryLetterCode"];
                $orderApi->BillingLastName = $invoice['billToName'];
                $orderApi->BillingAddressLine1 = $invoice['billingPostalAddress']["street"];
                $orderApi->BillingCity = $invoice['billingPostalAddress']["city"];
                $orderApi->BillingStateOrProvince =   $invoice['billingPostalAddress']["state"];
                $orderApi->BillingStateOrProvinceName =   $invoice['billingPostalAddress']["state"];
                $orderApi->BillingPostalCode =  $invoice['billingPostalAddress']["postalCode"];
                $orderApi->BillingCountry =  $invoice['billingPostalAddress']["countryLetterCode"];
                $webOrder->setInvoiceErp($invoice['number']);
                $webOrder->setContent($orderApi);
            } else {
                $output->writeln('<error>Data not accesible data business central ' . $amzOrderId . '</error>');
                return false;
            }
        } catch (\Exception $e) {
            $output->writeln('<error>Data not accesible data business central ' . $amzOrderId . '</error>');
            return false;
        }
        $this->manager->persist($webOrder);
        return true;
    }


    private function checkIfImport(AmazonOrder $amazonOrder)
    {
        if (array_key_exists($amazonOrder->getAmazonOrderId(), $this->toTransform)) {
            return false;
        }

        return true;
    }



    /**
     * Get Customer client according to profile
     *
     * @param string $profileId
     * @param string $siteId
     * @return string
     */
    private function matchSubChannel(string $subAmazon)
    {
        $mapCustomer = [
            "Amazon.co.uk" => "Amazon UK", // Customer Amazon UK
            "Amazon.it" =>   "Amazon Seller Central - IT", // Customer Amazon IT
            "Amazon.de" =>   "Amazon Seller Central - DE", // Customer Amazon DE
            "Amazon.fr" =>   "Amazon Seller Central - FR", // Customer Amazon FR
            "Amazon.es" =>   "Amazon Seller Central - ES", // Customer Amazon ES
        ];
        if (array_key_exists($subAmazon, $mapCustomer)) {
            return $mapCustomer[$subAmazon];
        } else {
            return null;
        }
    }




    private function recreateChannelObject($amzOrderId, WebOrder $webOrder)
    {
        $orderApi = new stdClass();
        $orderApi->Items = [];
        $orderRecordeds = $this->manager->getRepository(AmazonOrder::class)->findBy(
            [
                'amazonOrderId' => $amzOrderId,
            ]
        );

        $infoBase = $orderRecordeds[0];
        $webOrder->setPurchaseDate($infoBase->getPurchaseDate());
        $orderApi->TotalPrice = 0;
        $orderApi->TotalShippingPrice = 0;
        $orderApi->SiteName = $infoBase->getSalesChannel();
        $orderApi->SiteOrderID = $infoBase->getAmazonOrderId();
        $orderApi->SecondarySiteOrderID = null;
        $orderApi->Currency = $infoBase->getCurrency();
        $orderApi->CreatedDateUtc = $this->retourFormatDate($infoBase->getPurchaseDate());

        $orderApi->TotalTaxPrice = 0;
        $orderApi->TotalShippingTaxPrice = 0;
        $orderApi->TotalInsurancePrice = 0;
        $orderApi->TotalGiftOptionPrice = 0;
        $orderApi->TotalGiftOptionTaxPrice = 0;
        $orderApi->AdditionalCostOrDiscount = 0;
        $orderApi->OrderTags = "AutoGeneratedSku,AmazonInvoice";
        $orderApi->DistributionCenterTypeRollup = "ExternallyManaged";
        $orderApi->CheckoutStatus = "Completed";
        $orderApi->PaymentStatus = "Cleared";
        $orderApi->ShippingStatus = "Shipped";
        $orderApi->CheckoutDateUtc = $this->retourFormatDate($infoBase->getPurchaseDate());
        $orderApi->PaymentDateUtc = $this->retourFormatDate($infoBase->getPurchaseDate());
        $orderApi->ShippingDateUtc = $this->retourFormatDate($infoBase->getPurchaseDate());
        $orderApi->BuyerEmailAddress = "";
        $orderApi->BuyerEmailOptIn = false;
        $orderApi->OrderTaxType = "InclusiveVat";
        $orderApi->ShippingTaxType = "InclusiveVat";
        $orderApi->GiftOptionsTaxType = "InclusiveVat";
        $orderApi->PaymentMethod = "Amazon";
        $orderApi->ShippingTitle = null;
        $orderApi->ShippingFirstName = null;
        $orderApi->ShippingLastName = null;
        $orderApi->ShippingSuffix = null;
        $orderApi->ShippingCompanyName = null;
        $orderApi->ShippingCompanyJobTitle = null;
        $orderApi->ShippingDaytimePhone = null;
        $orderApi->ShippingEveningPhone = null;
        $orderApi->ShippingAddressLine1 = null;
        $orderApi->ShippingAddressLine2 = null;
        $orderApi->ShippingCity = $infoBase->getShipCity();
        $orderApi->ShippingStateOrProvince =  $infoBase->getShipState();
        $orderApi->ShippingStateOrProvinceName = $infoBase->getShipState();
        $orderApi->ShippingPostalCode = $infoBase->getShipPostalCode();
        $orderApi->ShippingCountry = $infoBase->getShipCountry();
        $orderApi->BillingTitle = null;
        $orderApi->BillingFirstName = null;
        $orderApi->BillingLastName = null;
        $orderApi->BillingSuffix = null;
        $orderApi->BillingCompanyName = null;
        $orderApi->BillingCompanyJobTitle = null;
        $orderApi->BillingDaytimePhone =  null;
        $orderApi->BillingEveningPhone =  null;
        $orderApi->BillingAddressLine1 = null;
        $orderApi->BillingAddressLine2 = null;
        $orderApi->BillingCity = $infoBase->getShipCity();
        $orderApi->BillingStateOrProvince =  $infoBase->getShipState();
        $orderApi->BillingStateOrProvinceName =  $infoBase->getShipState();
        $orderApi->BillingPostalCode = $infoBase->getShipPostalCode();
        $orderApi->BillingCountry = $infoBase->getShipCountry();
        $orderApi->PromotionCode = null;
        $orderApi->PromotionAmount = 0;

        foreach ($orderRecordeds as $orderRecord) {
            $line = new stdClass();
            $line->Sku = $orderRecord->getSku();
            $line->Title = $orderRecord->getProduct()->getDescription();
            $line->Quantity = $orderRecord->getQuantity();
            $line->UnitPrice = $orderRecord->getItemPriceCurrency();
            $line->TaxPrice = 0;
            $line->ShippingPrice = $orderRecord->getShippingPriceCurrency();
            $line->ShippingTaxPrice = 0;
            $line->RecyclingFee = 0;
            $line->UnitEstimatedShippingCost = 0;
            $line->GiftPrice = 0;
            $line->GiftTaxPrice = 0;
            $orderApi->TotalPrice = $orderApi->TotalPrice + $line->ShippingPrice + $line->Quantity *  $line->UnitPrice;
            $orderApi->TotalShippingPrice = $orderApi->TotalShippingPrice + $line->ShippingPrice;
            $orderApi->Items[] = $line;
        }

        return $orderApi;
    }



    public function retourFormatDate(\DateTimeInterface $date)
    {
        if ($date) {
            return $date->format('Y-m-d') . 'T' . $date->format('H:i:s') . "Z";
        } else {
            return '';
        }
    }
}
