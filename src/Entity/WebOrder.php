<?php

namespace App\Entity;

use App\Entity\IntegrationChannel;
use App\Helper\Traits\TraitLoggable;
use App\Helper\Traits\TraitTimeUpdated;
use App\Helper\Utils\DatetimeUtils;
use App\Service\Carriers\UpsGetTracking;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class WebOrder implements \Stringable
{
    use TraitTimeUpdated;

    use TraitLoggable;




    final public const  DOCUMENT_ORDER = 'ORDER';
    final public const  DOCUMENT_INVOICE = 'INVOICE';

    final public const  DEPOT_FBA_AMAZON = 'AMAZON';
    final public const  DEPOT_CENTRAL = 'CENTRAL';
    final public const  DEPOT_LAROCA = 'LAROCA';
    final public const  DEPOT_3PLUK = '3PLUK';
    final public const  DEPOT_3PLUE = '3PLUE';
    final public const  DEPOT_MADRID = 'MADRID';
    final public const  DEPOT_MIXED = 'MIXED';

    final public const  TIMING_INTEGRATION = 24;
    final public const  TIMING_SHIPPING = 30;
    final public const  TIMING_DELIVERY = 192;
    final public const  TIMING_COMPLETE = 30;

    final public const  FULFILLED_BY_EXTERNAL = 'EXTERNALLY MANAGED';
    final public const  FULFILLED_BY_SELLER = 'OWN MANAGED';
    final public const  FULFILLED_MIXED = 'MIXED MANAGED';


    final public const  CARRIER_DHL = 'DHL';
    final public const  CARRIER_DPDUK = 'DPDUK';
    final public const  CARRIER_ARISE = 'ARISE';
    final public const  CARRIER_FBA = 'FBA';
    final public const  CARRIER_UPS = 'UPS';
    final public const  CARRIER_DBSCHENKER = 'DBSCHENKER';
    final public const  CARRIER_SENDING = 'SENDING';
    final public const  CARRIER_CORREOSEXP = 'CORREOSEXP';
    final public const  CARRIER_TNT = 'TNT';
    final public const  CARRIER_CBL = 'CBL';
    

    final public const  STATE_ERROR_INVOICE = -2;
    final public const  STATE_ERROR = -1;
    final public const  STATE_CREATED = 0;
    final public const  STATE_SYNC_TO_ERP = 1;
    final public const  STATE_INVOICED = 5;
    final public const  STATE_COMPLETE = 6;
    final public const  STATE_CANCELLED = 7;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    private ?string $externalNumber = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON)]
    private $content;

    #[Assert\NotBlank]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    private ?string $orderErp = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    #[Assert\NotBlank]
    private ?string $invoiceErp = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON, nullable: true)]
    private $errors = [];

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $status = self::STATE_CREATED;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    private ?string $channel = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    private ?string $subchannel = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    private ?string $warehouse=self::DEPOT_LAROCA;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    private ?string $erpDocument = self::DOCUMENT_ORDER;


    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    private ?string $company = null;

    public $orderBCContent = [];

    public $comments;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $purchaseDate = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    private ?string $customerNumber = null;

    /**
     * Assert\Url(relativeProtocol = true)
     */
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    private ?string $trackingUrl = null;


    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    private ?string $fulfilledBy = self::FULFILLED_BY_SELLER;

    public $deliverySteps;
    public $amzEvents;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    private ?string $trackingCode = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    private ?string $carrierService = self::CARRIER_DHL;



    public function isFulfiledBySeller()
    {
        return $this->fulfilledBy == self::FULFILLED_BY_SELLER;
    }


    public function isFulfiledByExternal()
    {
        return $this->fulfilledBy == self::FULFILLED_BY_EXTERNAL;
    }


    

    public function isFulfiledByDhl()
    {
        return $this->fulfilledBy == self::FULFILLED_BY_SELLER && $this->carrierService == self::CARRIER_DHL;
    }

    public function isFulfiledByArise()
    {
        return $this->fulfilledBy == self::FULFILLED_BY_SELLER && $this->carrierService == self::CARRIER_ARISE;
    }



    public function getNbDaysSinceCreation()
    {
        $now = new DateTime();
        $interval = $now->diff($this->createdAt, true);
        return $interval->days;
    }
    
    

    public function getNbHoursSinceCreation()
    {
        $now = new DateTime();
        $interval = $now->diff($this->createdAt, true);
        return $interval->format('%a') * 24 + $interval->format('%h');
    }


    public function getNbHoursSincePurchaseDate()
    {
        $now = new DateTime();
        $interval = $now->diff($this->purchaseDate, true);
        return $interval->format('%a') * 24 + $interval->format('%h');
    }


    public function getOrderLinesContent()
    {
        return $this->getOrderContent();
    }

    public function getHeaderShippingContent()
    {
        return $this->getOrderContent();
    }

    public function getHeaderBillingContent()
    {
        return  $this->getOrderContent();
    }


    public function getOrderLinesBCContent()
    {
        return $this->orderBCContent;
    }

    public function getHeaderShippingBCContent()
    {
        return $this->orderBCContent;
    }

    public function getHeaderBillingBCContent()
    {
        return $this->orderBCContent;
    }

    public function haveInvoice()
    {
        return $this->invoiceErp != null;
    }

    public function needRetry()
    {
        return in_array($this->status, [self::STATE_ERROR, self::STATE_ERROR_INVOICE]);
    }

    public function canChangeStatusToInvoiced()
    {
        return in_array($this->status, [self::STATE_SYNC_TO_ERP]);
    }


    public function canChangeStatusToComplete()
    {
        return in_array($this->status, [self::STATE_INVOICED]);
    }




    public function getStatusLitteral()
    {
        if ($this->status ==  self::STATE_ERROR) {
            return "Error";
        } elseif ($this->status ==  self::STATE_SYNC_TO_ERP) {
            return $this->fulfilledBy == self::FULFILLED_BY_SELLER
                ? "Waiting for shipping"
                : "Waiting for invoicing";
        } elseif ($this->status ==  self::STATE_INVOICED) {
            return $this->fulfilledBy == self::FULFILLED_BY_SELLER
            ? "On delivery"
            : 'Invoice integrated';
        } elseif ($this->status ==  self::STATE_COMPLETE) {
            return  'Completed';
        } elseif ($this->status ==  self::STATE_CANCELLED) {
            return 'Cancelled';
        } elseif ($this->status ==  self::STATE_ERROR_INVOICE) {
            return 'Error send invoice';
        } else {
            return 'Undefined';
        }
    }


    public function getOrderErrors()
    {
        foreach ($this->errors as $error) {
            return $error['content'];
        }
        return '';
    }


    public function hasErrors()
    {
        return count($this->errors) > 0;
    }




    public function hasDelayTreatment()
    {
        $completedStates= [self::STATE_INVOICED, self::STATE_COMPLETE];
        if (
            $this->fulfilledBy == self::FULFILLED_BY_EXTERNAL
            && !in_array($this->status, $completedStates)
        ) {
            return DatetimeUtils::isOutOfDelay($this->createdAt, self::TIMING_INTEGRATION);
        } elseif (
            $this->fulfilledBy == self::FULFILLED_BY_SELLER
            && !in_array($this->status, $completedStates)
        ) {
            return DatetimeUtils::isOutOfDelayBusinessDays($this->purchaseDate, self::TIMING_SHIPPING);
        } elseif (
            $this->fulfilledBy == self::FULFILLED_BY_SELLER
            && $this->status == self::STATE_INVOICED
        ) {
            return DatetimeUtils::isOutOfDelayBusinessDays($this->purchaseDate, self::TIMING_DELIVERY);
        }
        return false;
    }

    public function getDelayProblemMessage()
    {
        $completedStates= [self::STATE_INVOICED, self::STATE_COMPLETE];
        if (
            $this->fulfilledBy == self::FULFILLED_BY_EXTERNAL
            && !in_array($this->status, $completedStates)
        ) {
            return 'Invoice should be done in ' . self::TIMING_INTEGRATION . ' hours  for ' . $this->__toString();
        } elseif (
            $this->fulfilledBy == self::FULFILLED_BY_SELLER
            && !in_array($this->status, $completedStates)
        ) {
            return 'Shipping should be processed in ' . self::TIMING_SHIPPING . ' hours  for ' . $this->__toString();
        } elseif (
            $this->fulfilledBy == self::FULFILLED_BY_SELLER
            && $this->status == self::STATE_INVOICED
        ) {
            return 'Delivery should be processed in max 8 days for ' . $this->__toString();
        }
        return 'No delay message for ' . $this->__toString();
    }

    public function __toString(): string
    {
        return 'Order ' . $this->subchannel . ' n°' . $this->externalNumber . ' (#' . $this->id . ')';
    }




    public function documentInErp()
    {
        if ($this->erpDocument && $this->status > 0) {
            return $this->erpDocument == self::DOCUMENT_INVOICE ?  $this->invoiceErp : $this->orderErp;
        }
        return '-';
    }


    public function getUrl()
    {
        $order = $this->getOrderContent();
        return match ($this->channel) {
            IntegrationChannel::CHANNEL_FITBITEXPRESS, IntegrationChannel::CHANNEL_ALIEXPRESS => 'https://gsp.aliexpress.com/apps/order/detail?orderId=' . $this->externalNumber,
            IntegrationChannel::CHANNEL_CHANNELADVISOR => 'https://sellercentral.amazon.fr/orders-v3/order/' . $this->externalNumber,
            IntegrationChannel::CHANNEL_OWLETCARE => 'https://owlet-spain.myshopify.com/admin/orders/' . $order['id'],
            IntegrationChannel::CHANNEL_MINIBATT => 'https://minibattstore.myshopify.com/admin/orders/' . $order['id'],
            IntegrationChannel::CHANNEL_REENCLE => 'https://4adafb-85.myshopify.com/admin/orders/' . $order['id'],
            IntegrationChannel::CHANNEL_FLASHLED => 'https://testflashled.myshopify.com/admin/orders/' . $order['id'],
            IntegrationChannel::CHANNEL_PAXUK => 'https://paxlabsuk.myshopify.com/admin/orders/' . $order['id'],
            IntegrationChannel::CHANNEL_FITBITCORPORATE => 'https://fitbitcorporate.myshopify.com/admin/orders/' . $order['id'],
            IntegrationChannel::CHANNEL_AMAZFIT_ARISE, IntegrationChannel::CHANNEL_SONOS_ARISE, IntegrationChannel::CHANNEL_ARISE, IntegrationChannel::CHANNEL_IMOU_ARISE => 'https://sellercenter.miravia.es/apps/order/detail?tradeOrderId=' . $this->externalNumber,
            IntegrationChannel::CHANNEL_LEROYMERLIN => 'https://adeo-marketplace.mirakl.net/mmp/shop/order/' . $order['id'],
            IntegrationChannel::CHANNEL_DECATHLON => 'https://marketplace-decathlon-eu.mirakl.net/mmp/shop/order/' . $order['id'],
            IntegrationChannel::CHANNEL_WORTEN => 'https://marketplace.worten.pt/mmp/shop/order/' . $order['id'],
            IntegrationChannel::CHANNEL_PCCOMPONENTES => 'https://pccomponentes-prod.mirakl.net/mmp/shop/order/' . $order['id'],
            IntegrationChannel::CHANNEL_BOULANGER => 'https://merchant.boulanger.com/mmp/shop/order/' . $order['id'],
            IntegrationChannel::CHANNEL_MEDIAMARKT => 'https://mediamarktsaturn.mirakl.net/mmp/shop/order/' . $order['id'],
            IntegrationChannel::CHANNEL_MANOMANO_DE, IntegrationChannel::CHANNEL_MANOMANO_IT, IntegrationChannel::CHANNEL_MANOMANO_ES, IntegrationChannel::CHANNEL_MANOMANO_FR => 'https://toolbox.manomano.com/orders',
            IntegrationChannel::CHANNEL_FNAC_FR => 'https://mp.fnacdarty.com/compte/vendeur/commande/' . $this->externalNumber,
            IntegrationChannel::CHANNEL_FNAC_ES => 'https://mp.fnacdarty.com/compte/vendeur/commande/' . $this->externalNumber,
            IntegrationChannel::CHANNEL_DARTY_FR => 'https://mp.fnacdarty.com/compte/vendeur/commande/' . $this->externalNumber,
            default => throw new Exception('No url link of weborder for ' . $this->channel),
        };
    }

    public static function createOneFromChannelAdvisor($orderApi): WebOrder
    {
        $webOrder = new WebOrder();
        $webOrder->setExternalNumber($orderApi->SiteOrderID);
        $webOrder->setChannel(IntegrationChannel::CHANNEL_CHANNELADVISOR);
        $webOrder->setSubchannel($orderApi->SiteName);
        $webOrder->setPurchaseDateFromString($orderApi->CreatedDateUtc);

        if ($orderApi->DistributionCenterTypeRollup == 'ExternallyManaged') {
            $webOrder->setWarehouse(WebOrder::DEPOT_FBA_AMAZON);
            $webOrder->setFulfilledBy(WebOrder::FULFILLED_BY_EXTERNAL);
            $webOrder->setCarrierService(WebOrder::CARRIER_FBA);
        } elseif ($orderApi->DistributionCenterTypeRollup == 'SellerManaged') {
            
        } else {
            $webOrder->setWarehouse(WebOrder::DEPOT_MIXED);
            $webOrder->setFulfilledBy(WebOrder::FULFILLED_MIXED);
        }
        $webOrder->addLog('Retrieved from ChannelAdvisorApi');
        $webOrder->setContent($orderApi);
        return $webOrder;
    }


    public function setPurchaseDateFromString($purchaseValue)
    {
        $this->purchaseDate =  DatetimeUtils::transformFromIso8601($purchaseValue);
    }



    

    public function isAShopifyOrder()
    {
        return in_array($this->channel, [
            IntegrationChannel::CHANNEL_OWLETCARE,
            IntegrationChannel::CHANNEL_PAXUK,
            IntegrationChannel::CHANNEL_PAXEU,
            IntegrationChannel::CHANNEL_FLASHLED,
            IntegrationChannel::CHANNEL_MINIBATT,
            IntegrationChannel::CHANNEL_REENCLE,
            IntegrationChannel::CHANNEL_FITBITCORPORATE,
        ]);
    }


    public function isAFnacOrder()
    {
        return in_array($this->channel, [
            IntegrationChannel::CHANNEL_FNAC_ES,
            IntegrationChannel::CHANNEL_FNAC_FR,
            IntegrationChannel::CHANNEL_DARTY_FR,
        ]);
    }


    public function isAChannelAdvisorOrder()
    {
        return in_array($this->channel, [
            IntegrationChannel::CHANNEL_CHANNELADVISOR
        ]);
    }


    public function isAMiraklOrder()
    {
        return in_array($this->channel, [
            IntegrationChannel::CHANNEL_MEDIAMARKT,
            IntegrationChannel::CHANNEL_DECATHLON,
            IntegrationChannel::CHANNEL_LEROYMERLIN,
            IntegrationChannel::CHANNEL_BOULANGER,
            IntegrationChannel::CHANNEL_WORTEN,
            IntegrationChannel::CHANNEL_PCCOMPONENTES,
        ]);
    }



    public function isAManoManoOrder()
    {
        return in_array($this->channel, [
            IntegrationChannel::CHANNEL_MANOMANO_ES,
            IntegrationChannel::CHANNEL_MANOMANO_DE,
            IntegrationChannel::CHANNEL_MANOMANO_IT,
            IntegrationChannel::CHANNEL_MANOMANO_FR
        ]);
    }



    public function isAliexpressOrder()
    {
        return in_array($this->channel, [
            IntegrationChannel::CHANNEL_FITBITEXPRESS,
            IntegrationChannel::CHANNEL_ALIEXPRESS,
        ]);
    }


    public function isAMiraviaOrder()
    {
        return in_array($this->channel, [
            IntegrationChannel::CHANNEL_ARISE,
            IntegrationChannel::CHANNEL_SONOS_ARISE,
            IntegrationChannel::CHANNEL_AMAZFIT_ARISE,
            IntegrationChannel::CHANNEL_IMOU_ARISE,
        ]);
    }



   


            

    
    public static function createOneFrom($orderApi, $channel): WebOrder
    {
        switch ($channel) {
            case IntegrationChannel::CHANNEL_ALIEXPRESS:
                return WebOrder::createOneFromAliExpress($orderApi);

            case IntegrationChannel::CHANNEL_FITBITEXPRESS:
                $webOrder = WebOrder::createOneFromAliExpress($orderApi);
                $webOrder->setChannel(IntegrationChannel::CHANNEL_FITBITEXPRESS);
                return $webOrder;

            case IntegrationChannel::CHANNEL_ARISE:
                return WebOrder::createOneFromArise($orderApi);

            case IntegrationChannel::CHANNEL_AMAZFIT_ARISE:
                $webOrder = WebOrder::createOneFromArise($orderApi);
                $webOrder->setChannel(IntegrationChannel::CHANNEL_AMAZFIT_ARISE);
                return $webOrder;

            case IntegrationChannel::CHANNEL_SONOS_ARISE:
                $webOrder = WebOrder::createOneFromArise($orderApi);
                $webOrder->setChannel(IntegrationChannel::CHANNEL_SONOS_ARISE);
                return $webOrder;
            case IntegrationChannel::CHANNEL_IMOU_ARISE:
                $webOrder = WebOrder::createOneFromArise($orderApi);
                $webOrder->setChannel(IntegrationChannel::CHANNEL_IMOU_ARISE);
                return $webOrder;
            
  
            
            case IntegrationChannel::CHANNEL_DECATHLON:
                return WebOrder::createOneFromDecathlon($orderApi);

            case IntegrationChannel::CHANNEL_PCCOMPONENTES:
                return WebOrder::createOneFromPcComponentes($orderApi); 
            
            case IntegrationChannel::CHANNEL_WORTEN:
                return WebOrder::createOneFromWorten($orderApi);  

            case IntegrationChannel::CHANNEL_LEROYMERLIN:
                return WebOrder::createOneFromLeroyMerlin($orderApi);
            
            case IntegrationChannel::CHANNEL_BOULANGER:
                return WebOrder::createOneFromBoulanger($orderApi);
            
            case IntegrationChannel::CHANNEL_MEDIAMARKT:
                return WebOrder::createOneFromMediaMarkt($orderApi);
                
            case IntegrationChannel::CHANNEL_CHANNELADVISOR:
                return WebOrder::createOneFromChannelAdvisor($orderApi);
                
            case IntegrationChannel::CHANNEL_OWLETCARE:
                return WebOrder::createOneFromOwletcare($orderApi);

            case IntegrationChannel::CHANNEL_PAXUK:
                return WebOrder::createOneFromPaxUK($orderApi);

            case IntegrationChannel::CHANNEL_FLASHLED:
                return WebOrder::createOneFromFlashled($orderApi);

            case IntegrationChannel::CHANNEL_MINIBATT:
                return WebOrder::createOneFromMinibatt($orderApi);
            
                case IntegrationChannel::CHANNEL_REENCLE:
                    return WebOrder::createOneFromReencle($orderApi);    
                
            case IntegrationChannel::CHANNEL_FITBITCORPORATE:
                return WebOrder::createOneFromFitbitCorporate($orderApi);

            case IntegrationChannel::CHANNEL_MANOMANO_DE:
                $webOrder = WebOrder::createOneFromManoMano($orderApi);
                $webOrder->setChannel(IntegrationChannel::CHANNEL_MANOMANO_DE);
                $webOrder->setSubchannel('Manomano.de');
                return $webOrder;
    
            case IntegrationChannel::CHANNEL_MANOMANO_FR:
                $webOrder = WebOrder::createOneFromManoMano($orderApi);
                $webOrder->setChannel(IntegrationChannel::CHANNEL_MANOMANO_FR);
                $webOrder->setSubchannel('Manomano.fr');
                return $webOrder;
    
            case IntegrationChannel::CHANNEL_MANOMANO_ES:
                $webOrder = WebOrder::createOneFromManoMano($orderApi);
                $webOrder->setChannel(IntegrationChannel::CHANNEL_MANOMANO_ES);
                $webOrder->setSubchannel('Manomano.es');
                return $webOrder;
                
            case IntegrationChannel::CHANNEL_MANOMANO_IT:
                $webOrder = WebOrder::createOneFromManoMano($orderApi);
                $webOrder->setChannel(IntegrationChannel::CHANNEL_MANOMANO_IT);
                $webOrder->setSubchannel('Manomano.it');
                return $webOrder;

            case IntegrationChannel::CHANNEL_FNAC_ES:
                $webOrder = WebOrder::createOrderFromFnac($orderApi);
                $webOrder->setChannel(IntegrationChannel::CHANNEL_FNAC_ES);
                $webOrder->setSubchannel('Fnac.es');
                return $webOrder;

            case IntegrationChannel::CHANNEL_FNAC_FR:
                $webOrder = WebOrder::createOrderFromFnac($orderApi);
                $webOrder->setChannel(IntegrationChannel::CHANNEL_FNAC_FR);
                $webOrder->setSubchannel('Fnac.fr');
                return $webOrder;

            case IntegrationChannel::CHANNEL_DARTY_FR:
                $webOrder = WebOrder::createOrderFromFnac($orderApi);
                $webOrder->setChannel(IntegrationChannel::CHANNEL_DARTY_FR);
                $webOrder->setSubchannel('Darty.fr');
                return $webOrder;
            
        }

        throw new Exception('No constructor of weborder for ' . $channel);
    }


    public static function createOneFromOwletcare($orderApi): WebOrder
    {
        $webOrder = WebOrder::createOrderFromShopify($orderApi);
        $webOrder->setExternalNumber('OWL-' . $orderApi['order_number']);
        $webOrder->setChannel(IntegrationChannel::CHANNEL_OWLETCARE);
        $webOrder->setSubchannel('Owletbaby.es');
        $webOrder->addLog('Retrieved from Owletbaby.es');
        return $webOrder;
    }


    public static function createOneFromPaxUK($orderApi): WebOrder
    {
        $webOrder = WebOrder::createOrderFromShopify($orderApi);
        $webOrder->setExternalNumber('PAXUK-' . $orderApi['order_number']);
        $webOrder->setChannel(IntegrationChannel::CHANNEL_PAXUK);
        $webOrder->setSubchannel('Uk.pax.com');
        $webOrder->addLog('Retrieved from uk.pax.com');
        return $webOrder;
    }



    public static function createOneFromPaxEU($orderApi): WebOrder
    {
        $webOrder = WebOrder::createOrderFromShopify($orderApi);
        $webOrder->setExternalNumber('PAXEU-' . $orderApi['order_number']);
        $webOrder->setChannel(IntegrationChannel::CHANNEL_PAXEU);
        $webOrder->setSubchannel('Eu.pax.com');
        $webOrder->addLog('Retrieved from eu.pax.com');
        return $webOrder;
    }


    public static function createOneFromMinibatt($orderApi): WebOrder
    {
        $webOrder = WebOrder::createOrderFromShopify($orderApi);
        $webOrder->setExternalNumber('MNB-' . $orderApi['order_number']);
        $webOrder->setChannel(IntegrationChannel::CHANNEL_MINIBATT);
        $webOrder->setSubchannel('Minibatt.com');
        $webOrder->addLog('Retrieved from Minibatt.com');
        return $webOrder;
    }

    public static function createOneFromReencle($orderApi): WebOrder
    {
        $webOrder = WebOrder::createOrderFromShopify($orderApi);
        $webOrder->setExternalNumber('RNC-' . $orderApi['order_number']);
        $webOrder->setChannel(IntegrationChannel::CHANNEL_REENCLE);
        $webOrder->setSubchannel('Reencle.shop');
        $webOrder->addLog('Retrieved from Reencle.shop');
        return $webOrder;
    }


    

    public static function createOneFromFitbitCorporate($orderApi): WebOrder
    {
        $webOrder = WebOrder::createOrderFromShopify($orderApi);
        $webOrder->setExternalNumber('FBT-' . $orderApi['order_number']);
        $webOrder->setChannel(IntegrationChannel::CHANNEL_FITBITCORPORATE);
        $webOrder->setSubchannel('Fitbitcorporate.kps.tech');
        $webOrder->addLog('Retrieved from Fitbitcorporate.kps.tech');
        return $webOrder;
    }


    public static function createOneFromFlashled($orderApi): WebOrder
    {
        $webOrder = WebOrder::createOrderFromShopify($orderApi);
        $webOrder->setExternalNumber('FLS-' . $orderApi['order_number']);
        $webOrder->setChannel(IntegrationChannel::CHANNEL_FLASHLED);
        $webOrder->setSubchannel('Flashled.es');
        $webOrder->addLog('Retrieved from Flashled.es');
        return $webOrder;
    }


    public static function createOrderFromShopify($orderApi): WebOrder
    {
        $webOrder = new WebOrder();
        $webOrder->setPurchaseDate(DatetimeUtils::transformFromIso8601($orderApi['processed_at']));
        $webOrder->setContent($orderApi);
        return $webOrder;
    }






    public static function createOrderFromFnac($orderApi): WebOrder
    {
        $webOrder = new WebOrder();
        $webOrder->setPurchaseDate(DatetimeUtils::transformFromIso8601($orderApi['created_at']));
        $webOrder->setExternalNumber($orderApi['order_id']);
        $webOrder->setContent($orderApi);
        return $webOrder;
    }



    public static function createOneFromAliExpress($orderApi): WebOrder
    {
        $webOrder = new WebOrder();
        $webOrder->setExternalNumber($orderApi->id);
        $webOrder->setChannel(IntegrationChannel::CHANNEL_ALIEXPRESS);
        $webOrder->setSubchannel('AliExpress');
        $datePurchase = DatetimeUtils::createDateTimeFromDateWithDelay($orderApi->gmt_pay_success);
        $webOrder->setPurchaseDate($datePurchase);
        $webOrder->addLog('Retrieved from Aliexpress');
        $webOrder->setContent($orderApi);
        return $webOrder;
    }




    public static function createOneFromArise($orderApi): WebOrder
    {
        $webOrder = new WebOrder();
        $webOrder->setExternalNumber($orderApi->order_id);
        $webOrder->setChannel(IntegrationChannel::CHANNEL_ARISE);
        $webOrder->setSubchannel('Miravia.es');
        $datePurchase = new DateTime($orderApi->created_at, new DateTimeZone('Europe/London'));
        $datePurchase->setTimezone(new DateTimeZone('Europe/Paris'));
        $webOrder->setPurchaseDate($datePurchase);
       
        foreach ($orderApi->lines as $line) {
            if ($line->delivery_option_sof==1) {
                $webOrder->setCarrierService(WebOrder::CARRIER_DHL);
            } else {
                $webOrder->setCarrierService(WebOrder::CARRIER_ARISE);
            }
        }
        
        $webOrder->addLog('Retrieved from Miravia');
        $webOrder->setContent($orderApi);
        return $webOrder;
    }


    public static function createOneFromLeroyMerlin($orderApi): WebOrder
    {
        $webOrder = WebOrder::createOrderFromMirakl($orderApi);
        $webOrder->setSubchannel("Leroy Merlin ".$orderApi['channel']['label']);
        $webOrder->setChannel(IntegrationChannel::CHANNEL_LEROYMERLIN);
        return $webOrder;
    }


    public static function createOneFromWorten($orderApi): WebOrder
    {
        $webOrder = WebOrder::createOrderFromMirakl($orderApi);
        $webOrder->setSubchannel("Worten ".$orderApi['channel']['label']);
        $webOrder->setChannel(IntegrationChannel::CHANNEL_WORTEN);
        return $webOrder;
    }

    public static function createOneFromPcComponentes($orderApi): WebOrder
    {
        $webOrder = WebOrder::createOrderFromMirakl($orderApi);
        $webOrder->setSubchannel("PcComponentes ".$orderApi['channel']['label']);
        $webOrder->setChannel(IntegrationChannel::CHANNEL_PCCOMPONENTES);
        return $webOrder;
    }


    public static function createOneFromDecathlon($orderApi): WebOrder
    {
        $webOrder = WebOrder::createOrderFromMirakl($orderApi);
        $webOrder->setSubchannel("Decathlon ".$orderApi['channel']['label']);
        $webOrder->setChannel(IntegrationChannel::CHANNEL_DECATHLON);
        foreach ($orderApi['order_additional_fields'] as $field) {
            if ($field['code']=='orderid') {
                $webOrder->setExternalNumber($field['value']);
            }
        }

        return $webOrder;
    }

    public static function createOneFromBoulanger($orderApi): WebOrder
    {
        $webOrder = WebOrder::createOrderFromMirakl($orderApi);
        $webOrder->setSubchannel("Boulanger");
        $webOrder->setChannel(IntegrationChannel::CHANNEL_BOULANGER);
        return $webOrder;
    }



    public static function createOneFromMediaMarkt($orderApi): WebOrder
    {
        $webOrder = WebOrder::createOrderFromMirakl($orderApi);
        $webOrder->setSubchannel("MediaMarkt.es");
        $webOrder->setChannel(IntegrationChannel::CHANNEL_MEDIAMARKT);
        return $webOrder;
    }



    



    public static function createOrderFromMirakl($orderApi): WebOrder
    {
        $webOrder = new WebOrder();
        $webOrder->setPurchaseDate(DatetimeUtils::transformFromIso8601($orderApi['created_date']));
        $webOrder->setContent($orderApi);
        $webOrder->setExternalNumber($orderApi['id']);
        if(array_key_exists('channel', $orderApi)) {
            $webOrder->addLog('Retrieved from '.$orderApi['channel']['code'].' '.$orderApi['channel']['label']);
        } else {
            $webOrder->addLog('Retrieved from marketplace');

        }
        
        return $webOrder;
    }


    public static function createOneFromManoMano($orderApi): WebOrder
    {
        $webOrder = new WebOrder();
        $webOrder->setPurchaseDate(DatetimeUtils::transformFromIso8601($orderApi['created_at']));
        $webOrder->setContent($orderApi);
        $webOrder->setExternalNumber($orderApi['order_reference']);
        $webOrder->addLog('Retrieved from ManoMano');
        return $webOrder;
    }

    /**
     * Undocumented function
     *
     * @param string $content
     * @return void
     */
    public function addError($content)
    {
        $this->errors[] = [
            'date' => date('d-m-Y H:i:s'),
            'content' => $content,
        ];
        $this->addLog($content, 'error');
    }




    public function cleanErrors(): self
    {
        $this->errors = [];
        return $this;
    }


    public function getOrderContent()
    {
        if($this->isAFnacOrder()
            ||$this->isAManoManoOrder()
            ||$this->isAMiraklOrder()
            ||$this->isAShopifyOrder()
        ) {
            return $this->getContent();
        } else {
            return json_decode(json_encode($this->getContent()));
        }
        
    }




    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExternalNumber(): ?string
    {
        return $this->externalNumber;
    }

    public function setExternalNumber(string $externalNumber): self
    {
        $this->externalNumber = $externalNumber;

        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getOrderErp(): ?string
    {
        return $this->orderErp;
    }

    public function setOrderErp(?string $orderErp): self
    {
        $this->orderErp = $orderErp;

        return $this;
    }

    public function getInvoiceErp(): ?string
    {
        return $this->invoiceErp;
    }

    public function setInvoiceErp(?string $invoiceErp): self
    {
        $this->invoiceErp = $invoiceErp;

        return $this;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    public function setErrors(?array $errors): self
    {
        $this->errors = $errors;

        return $this;
    }



    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function setChannel(string $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    public function getSubchannel(): ?string
    {
        return $this->subchannel;
    }

    public function setSubchannel(?string $subchannel): self
    {
        $this->subchannel = $subchannel;

        return $this;
    }

    public function getWarehouse(): ?string
    {
        return $this->warehouse;
    }

    public function setWarehouse(string $warehouse): self
    {
        $this->warehouse = $warehouse;

        return $this;
    }

    public function getErpDocument(): ?string
    {
        return $this->erpDocument;
    }

    public function setErpDocument(string $erpDocument): self
    {
        $this->erpDocument = $erpDocument;

        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(string $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getFulfilledBy(): ?string
    {
        return $this->fulfilledBy;
    }

    public function setFulfilledBy(?string $fulfilledBy): self
    {
        $this->fulfilledBy = $fulfilledBy;

        return $this;
    }

    public function getPurchaseDate(): ?DateTimeInterface
    {
        return $this->purchaseDate;
    }

    public function setPurchaseDate(?DateTimeInterface $purchaseDate): self
    {
        $this->purchaseDate = $purchaseDate;
        return $this;
    }

    public function getCustomerNumber(): ?string
    {
        return $this->customerNumber;
    }

    public function setCustomerNumber(?string $customerNumber): self
    {
        $this->customerNumber = $customerNumber;

        return $this;
    }

    public function getTrackingUrl(): ?string
    {
        return $this->trackingUrl;
    }

    public function setTrackingUrl(?string $trackingUrl): self
    {
        $this->trackingUrl = $trackingUrl;

        return $this;
    }

    public function getTrackingCode(): ?string
    {
        return $this->trackingCode;
    }

    public function setTrackingCode(?string $trackingCode): self
    {
        $this->trackingCode = $trackingCode;

        return $this;
    }

    public function getCarrierService(): ?string
    {
        return $this->carrierService;
    }

    public function setCarrierService(?string $carrierService): self
    {
        $this->carrierService = $carrierService;

        return $this;
    }
}
