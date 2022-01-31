<?php

namespace App\Command\ChannelAdvisor;

use App\Entity\AmazonOrder;
use App\Entity\IntegrationFile;
use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Service\BusinessCentral\KpFranceConnector;
use App\Service\ChannelAdvisor\ChannelWebservice;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use stdClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildHistoricCommand extends Command
{
    protected static $defaultName = 'app:channel-build-historic';
    protected static $defaultDescription = 'Build historical orders';

    public function __construct(KpFranceConnector $saleOrderConnector, ChannelWebservice $channelWebservice, ManagerRegistry $manager)
    {
        $this->manager = $manager->getManager();
        $this->bcConnector = $saleOrderConnector;
        $this->channelWebservice = $channelWebservice;
        parent::__construct();
    }

    private $bcConnector;

    private $manager;

    private $channelWebservice;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('orderNumbers', InputArgument::OPTIONAL, 'Orders number', 100);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $counter = 0;
        $q = $this->manager->createQuery('select f from App\Entity\IntegrationFile f');

        foreach ($q->toIterable() as $integrationFile) {
            $output->writeln('____________________');
            $output->writeln('Order ' . $integrationFile->getExternalOrderId() . ' #' . $counter);
            if ($this->treatFromChannel($integrationFile, $output)) {
                $counter++;
            }


            if ($counter % 20 == 0) {
                $this->manager->flush();
                $this->manager->clear();
            }

            if ($counter > $input->getArgument('orderNumbers')) {
                $this->manager->flush();
                return Command::SUCCESS;
            }
        }

        $this->manager->flush();


        return Command::SUCCESS;
    }




    public function treatFromChannel(IntegrationFile $integrationFile, OutputInterface $output)
    {
        if ($this->checkIfWebOrderExists($integrationFile->getExternalOrderId())) {
            $output->writeln('Already integrated');
            return false;
        }
        $webOrder = new WebOrder();
        $webOrder->setExternalNumber($integrationFile->getExternalOrderId());
        $webOrder->setStatus(WebOrder::STATE_INVOICED);
        $webOrder->setChannel(WebOrder::CHANNEL_CHANNELADVISOR);
        $webOrder->setSubchannel($this->matchChannelAdvisorOrderToCustomer($integrationFile->getProfileChannel()));
        $webOrder->setInvoiceErp($integrationFile->getDocumentNumber());
        $webOrder->setErpDocument(WebOrder::DOCUMENT_INVOICE);
        $webOrder->setWarehouse(WebOrder::DEPOT_FBA_AMAZON);
        $webOrder->setFulfilledBy(WebOrder::FULFILLED_BY_EXTERNAL);
        $webOrder->setCompany(BusinessCentralConnector::KP_FRANCE);
        $webOrder->addLog('Build from integration file');
        $orderApi = $this->recreateChannelObject($integrationFile, $webOrder);

        $retrieved = false;


        try {
            $orderChannel = $this->channelWebservice->getFullOrder($integrationFile->getChannelOrderId());
            if ($orderChannel) {
                $orderApi = $orderChannel;
                $retrieved = true;
            }
            $output->writeln('Retrieved data from channel');
            $webOrder->addLog('Retrieved data from channel');
        } catch (\Exception $e) {
            $output->writeln('Data not accesible data channel');
            $webOrder->addLog('Data not accesible data from channel');
        }

        $webOrder->setContent($orderApi);

        try {
            $invoice = $this->bcConnector->getSaleInvoiceByNumber($integrationFile->getDocumentNumber());
            if ($invoice) {
                $output->writeln('Retrieved data from business central');
                $webOrder->addLog('Retrieved data from business central');
                $webOrder->setOrderErp($invoice['orderNumber']);
                if ($retrieved == false || (strlen($orderApi->ShippingLastName) == 0 && $retrieved == true)) {
                    $orderApi->ShippingLastName = $invoice['shipToName'];
                    $orderApi->ShippingAddressLine1 = $invoice['shippingPostalAddress']["street"];;
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
                }
            }
        } catch (\Exception $e) {
            $output->writeln('Data not accesible data business central');
            $webOrder->addLog('Data not accesible data business central');
        }
        $this->manager->persist($webOrder);
        return true;
    }


    private function checkIfWebOrderExists($orderNumber)
    {
        $orderRecorded = $this->manager->getRepository(WebOrder::class)->findBy(
            [
                'externalNumber' => $orderNumber,
                'channel' => WebOrder::CHANNEL_CHANNELADVISOR
            ]
        );
        return count($orderRecorded) > 0;
    }




    /**
     * Get Customer client according to profile 
     *
     * @param string $profileId
     * @param string $siteId
     * @return string
     */
    private function matchChannelAdvisorOrderToCustomer(string $profileId): string
    {
        $mapCustomer = [
            "12010024" =>   "Amazon UK", // Customer Amazon UK
            "12010025" =>   "Amazon Seller Central - IT", // Customer Amazon IT
            "12010023" =>   "Amazon Seller Central - DE", // Customer Amazon DE
            "12009934" =>   "Amazon Seller Central - FR", // Customer Amazon FR
            "12010026" =>   "Amazon Seller Central - ES", // Customer Amazon ES
        ];
        if (array_key_exists($profileId, $mapCustomer)) {
            return $mapCustomer[$profileId];
        } else {
            throw new Exception("Profile Id $profileId");
        }
    }


    private function recreateChannelObject(IntegrationFile $integrationFile, WebOrder $webOrder)
    {

        $orderApi = new stdClass();
        $orderApi->Items = [];
        $orderRecordeds = $this->manager->getRepository(AmazonOrder::class)->findBy(
            [
                'amazonOrderId' => $integrationFile->getExternalOrderId(),
            ]
        );


        if (count($orderRecordeds) == 0) {
            return $orderApi;
        }


        $infoBase = $orderRecordeds[0];

        $webOrder->setPurchaseDate($infoBase->getPurchaseDate());

        $orderApi->TotalPrice = 0;
        $orderApi->TotalShippingPrice = 0;

        $orderApi->ID = $integrationFile->getChannelOrderId();
        $orderApi->ProfileID = $integrationFile->getProfileChannel();
        $orderApi->SiteName = $this->matchChannelAdvisorOrderToCustomer($integrationFile->getProfileChannel());
        $orderApi->SiteOrderID = $integrationFile->getExternalOrderId();
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
            $line->ProfileID = $integrationFile->getProfileChannel();
            $line->OrderID = $integrationFile->getChannelOrderId();;
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
