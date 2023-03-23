<?php

namespace App\Service\Amazon;

use AmazonPHP\SellingPartner\Marketplace;
use AmazonPHP\SellingPartner\Model\FulfillmentInbound\Address;
use AmazonPHP\SellingPartner\Model\FulfillmentInbound\Condition;
use AmazonPHP\SellingPartner\Model\FulfillmentInbound\CreateInboundShipmentPlanRequest;
use AmazonPHP\SellingPartner\Model\FulfillmentInbound\InboundShipmentHeader;
use AmazonPHP\SellingPartner\Model\FulfillmentInbound\InboundShipmentItem;
use AmazonPHP\SellingPartner\Model\FulfillmentInbound\InboundShipmentPlanItem;
use AmazonPHP\SellingPartner\Model\FulfillmentInbound\InboundShipmentPlanRequestItem;
use AmazonPHP\SellingPartner\Model\FulfillmentInbound\InboundShipmentRequest;
use AmazonPHP\SellingPartner\Model\FulfillmentInbound\IntendedBoxContentsSource;
use AmazonPHP\SellingPartner\Model\FulfillmentInbound\LabelPrepPreference;
use AmazonPHP\SellingPartner\Model\FulfillmentInbound\ShipmentStatus;
use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\BusinessCentral\Connector\KpFranceConnector;
use App\Entity\Product;
use App\Entity\ProductCorrelation;
use App\Helper\MailService;
use App\Service\Amazon\AmzApi;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;

class AmzApiInbound
{
    protected $mailer;

    protected $manager;

    protected $logger;

    protected $amzApi;

    protected $kpFranceConnector;

    protected $dateNow;

    public function __construct(
        LoggerInterface $logger,
        AmzApi $amzApi,
        ManagerRegistry $manager,
        MailService $mailer,
        KitPersonalizacionSportConnector $kpFranceConnector
    ) {
        $this->logger = $logger;
        $this->amzApi = $amzApi;
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->kpFranceConnector = $kpFranceConnector;
    }



    public function getLabels($shipmentId)
    {
        return $this->amzApi->getLabels($shipmentId);
    }

    public function sendInbounds()
    {
        $inbounds = $this->kpFranceConnector->getTransfersOrderToFba();
        foreach ($inbounds as $inbound) {
            $this->integrateInbound($inbound);
            return;
        }
    }



    public function integrateInbound(array $inbound)
    {
        $country = 'ES';
        $laRoca= new Address(
            [
                'name' => 'Logistica cel ',
                'address_line1' => 'Carrer Isaac Newton, 8',
                'city' => 'La Roca del Vallès',
                'state_or_province_code' => 'Barcelona',
                'country_code' => 'ES',
                'postal_code' => '08430',
        ]
        );

        $inboundPlan = new CreateInboundShipmentPlanRequest();
        $inboundPlan->setShipToCountryCode($country);
        $inboundPlan->setLabelPrepPreference(new LabelPrepPreference(LabelPrepPreference::AMAZON_LABEL_ONLY));
        $inboundPlan->setShipFromAddress($laRoca);

        $items = [];
        foreach ($inbound['transferOrderLines'] as $transferOrder) {
            $itemLine = new InboundShipmentPlanRequestItem();
            $itemLine->setQuantity(600);
            $itemLine->setQuantityInCase(5);

            $product = $this->manager->getRepository(Product::class)->findOneBySku($transferOrder['itemNo']);
            $productCorrelation = $this->manager->getRepository(ProductCorrelation::class)->findOneByProduct($product);
            if ($productCorrelation) {
                $itemLine->setSellerSku($productCorrelation->getSkuUsed());
            } else {
                $itemLine->setSellerSku($transferOrder['itemNo']);
            }
            
            $itemLine->setCondition(new Condition(Condition::NEW_ITEM));
            if ($product) {
                $itemLine->setAsin($product->getAsin());
            }
            $items[]=$itemLine;
            break;
        }

        $inboundPlan->setInboundShipmentPlanRequestItems($items);

        $response = $this->amzApi->createInboundPlan($inboundPlan);

        foreach ($response->getPayload()->getInboundShipmentPlans() as $inboundShipmentPlan) {
            $inboudnShipmentRequest = new InboundShipmentRequest();
            $inboudnShipmentRequest->setMarketplaceId(Marketplace::fromCountry($country)->id());
            $shipmentHeader = new InboundShipmentHeader();
            $shipmentHeader->setShipmentName($inbound['number']);
            $shipmentHeader->setDestinationFulfillmentCenterId($inboundShipmentPlan->getDestinationFulfillmentCenterId());
            $shipmentHeader->setShipFromAddress($laRoca);
            $shipmentHeader->setShipmentStatus(new ShipmentStatus(ShipmentStatus::WORKING));
            $shipmentHeader->setLabelPrepPreference(new LabelPrepPreference(LabelPrepPreference::AMAZON_LABEL_ONLY));
            $shipmentHeader->setDestinationFulfillmentCenterId($inboundShipmentPlan->getDestinationFulfillmentCenterId());
            $shipmentHeader->setAreCasesRequired(true);
            $shipmentHeader->setIntendedBoxContentsSource(new IntendedBoxContentsSource(IntendedBoxContentsSource::FEED));
            $inboudnShipmentRequest->setInboundShipmentHeader($shipmentHeader);

            $list= [];
            foreach ($inboundShipmentPlan->getItems() as $item) {
                $itemNew = new InboundShipmentItem();
                $itemNew->setQuantityInCase(5);
                $itemNew->setSellerSku($item->getSellerSku());
                $itemNew->setFulfillmentNetworkSku($item->getFulfillmentNetworkSku());
                $itemNew->setQuantityShipped(600);
                $list[] = $itemNew;
            }
            $inboudnShipmentRequest->setInboundShipmentItems($list);

            $reponse = $this->amzApi->createInbound($inboundShipmentPlan->getShipmentId(), $inboudnShipmentRequest);
            return $reponse;
        }
    }
}
