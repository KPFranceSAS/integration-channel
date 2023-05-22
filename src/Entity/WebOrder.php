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

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class WebOrder
{
    use TraitTimeUpdated;

    use TraitLoggable;




    public const  DOCUMENT_ORDER = 'ORDER';
    public const  DOCUMENT_INVOICE = 'INVOICE';

    public const  DEPOT_FBA_AMAZON = 'AMAZON';
    public const  DEPOT_CENTRAL = 'CENTRAL';
    public const  DEPOT_LAROCA = 'LAROCA';
    public const  DEPOT_3PLUK = '3PLUK';
    public const  DEPOT_3PLUE = '3PLUE';
    public const  DEPOT_MADRID = 'MADRID';
    public const  DEPOT_MIXED = 'MIXED';

    public const  TIMING_INTEGRATION = 24;
    public const  TIMING_SHIPPING = 30;
    public const  TIMING_DELIVERY = 192;

    public const  FULFILLED_BY_EXTERNAL = 'EXTERNALLY MANAGED';
    public const  FULFILLED_BY_SELLER = 'OWN MANAGED';
    public const  FULFILLED_MIXED = 'MIXED MANAGED';


    public const  CARRIER_DHL = 'DHL';
    public const  CARRIER_ARISE = 'ARISE';
    public const  CARRIER_FBA = 'FBA';
    public const  CARRIER_UPS = 'UPS';

    public const  STATE_ERROR_INVOICE = -2;
    public const  STATE_ERROR = -1;
    public const  STATE_CREATED = 0;
    public const  STATE_SYNC_TO_ERP = 1;
    public const  STATE_INVOICED = 5;
    public const  STATE_COMPLETE = 6;
    public const  STATE_CANCELLED = 7;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $externalNumber;

    /**
     * @ORM\Column(type="json")
     */
    private $content;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $orderErp;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank()
     */
    private $invoiceErp;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $errors = [];

    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $channel;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $subchannel;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $warehouse;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $erpDocument;


    /**
     * @ORM\Column(type="string", length=255)
     */
    private $company;

    public $orderBCContent = [];

    public $comments;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $purchaseDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $customerNumber;

    /**
     * Assert\Url(relativeProtocol = true)
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $trackingUrl;


    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fulfilledBy;

    public $deliverySteps;
    public $amzEvents;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $trackingCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $carrierService;



    public function isFulfiledBySeller()
    {
        return $this->fulfilledBy == self::FULFILLED_BY_SELLER;
    }

    public function isFulfiledByDhl()
    {
        return $this->fulfilledBy == self::FULFILLED_BY_SELLER && $this->carrierService == self::CARRIER_DHL;
    }

    public function isFulfiledByArise()
    {
        return $this->fulfilledBy == self::FULFILLED_BY_SELLER && $this->carrierService == self::CARRIER_ARISE;
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

    public function __toString()
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
        switch ($this->channel) {
            case IntegrationChannel::CHANNEL_FITBITEXPRESS:
            case IntegrationChannel::CHANNEL_ALIEXPRESS:
                return 'https://gsp.aliexpress.com/apps/order/detail?orderId=' . $this->externalNumber;
            case IntegrationChannel::CHANNEL_CHANNELADVISOR:
                return 'https://sellercentral.amazon.fr/orders-v3/order/' . $this->externalNumber;
            case IntegrationChannel::CHANNEL_OWLETCARE:
                return 'https://owlet-spain.myshopify.com/admin/orders/' . $order['id'];
            case IntegrationChannel::CHANNEL_MINIBATT:
                return 'https://minibattstore.myshopify.com/admin/orders/' . $order['id'];
            case IntegrationChannel::CHANNEL_FLASHLED:
                return 'https://testflashled.myshopify.com/admin/orders/' . $order['id'];
            case IntegrationChannel::CHANNEL_FITBITCORPORATE:
                return 'https://fitbitcorporate.myshopify.com/admin/orders/' . $order['id'];
            case IntegrationChannel::CHANNEL_AMAZFIT_ARISE:
            case IntegrationChannel::CHANNEL_SONOS_ARISE:
            case IntegrationChannel::CHANNEL_ARISE:
                return 'https://sellercenter.miravia.es/apps/order/detail?tradeOrderId=' . $this->externalNumber;
            case IntegrationChannel::CHANNEL_LEROYMERLIN:
                return 'https://adeo-marketplace.mirakl.net/mmp/shop/order/' . $order['id'];
            case IntegrationChannel::CHANNEL_DECATHLON:
                return 'https://marketplace-decathlon-eu.mirakl.net/mmp/shop/order/' . $order['id'];
            case IntegrationChannel::CHANNEL_BOULANGER:
                return 'https://merchant.boulanger.com/mmp/shop/order/' . $order['id'];
            case IntegrationChannel::CHANNEL_MANOMANO_DE:
            case IntegrationChannel::CHANNEL_MANOMANO_IT:
            case IntegrationChannel::CHANNEL_MANOMANO_ES:
            case IntegrationChannel::CHANNEL_MANOMANO_FR:
                return 'https://toolbox.manomano.com/orders';


        }
        throw new Exception('No url link of weborder for ' . $this->channel);
    }

    public static function createOneFromChannelAdvisor($orderApi): WebOrder
    {
        $webOrder = new WebOrder();
        $webOrder->setExternalNumber($orderApi->SiteOrderID);
        $webOrder->setStatus(WebOrder::STATE_CREATED);
        $webOrder->setChannel(IntegrationChannel::CHANNEL_CHANNELADVISOR);
        $webOrder->setSubchannel($orderApi->SiteName);
        $webOrder->setErpDocument(WebOrder::DOCUMENT_ORDER);
        $webOrder->setPurchaseDateFromString($orderApi->CreatedDateUtc);

        if ($orderApi->DistributionCenterTypeRollup == 'ExternallyManaged') {
            $webOrder->setWarehouse(WebOrder::DEPOT_FBA_AMAZON);
            $webOrder->setFulfilledBy(WebOrder::FULFILLED_BY_EXTERNAL);
            $webOrder->setCarrierService(WebOrder::CARRIER_FBA);
        } elseif ($orderApi->DistributionCenterTypeRollup == 'SellerManaged') {
            $webOrder->setWarehouse(WebOrder::DEPOT_LAROCA);
            $webOrder->setFulfilledBy(WebOrder::FULFILLED_BY_SELLER);
            $skus = [];
            foreach ($orderApi->Items as $line) {
                $skus[] = $line->Sku;
            }
            $shouldBeSentByUps = UpsGetTracking::shouldBeSentWith($skus);
            if ($shouldBeSentByUps) {
                $webOrder->setCarrierService(WebOrder::CARRIER_UPS);
            } else {
                $webOrder->setCarrierService(WebOrder::CARRIER_DHL);
            }            
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
            
            case IntegrationChannel::CHANNEL_DECATHLON:
                return WebOrder::createOneFromDecathlon($orderApi);

            case IntegrationChannel::CHANNEL_LEROYMERLIN:
                return WebOrder::createOneFromLeroyMerlin($orderApi);
            
            case IntegrationChannel::CHANNEL_BOULANGER:
                return WebOrder::createOneFromBoulanger($orderApi);
                
            case IntegrationChannel::CHANNEL_CHANNELADVISOR:
                return WebOrder::createOneFromChannelAdvisor($orderApi);
                
            case IntegrationChannel::CHANNEL_OWLETCARE:
                return WebOrder::createOneFromOwletcare($orderApi);

            case IntegrationChannel::CHANNEL_FLASHLED:
                return WebOrder::createOneFromFlashled($orderApi);

            case IntegrationChannel::CHANNEL_MINIBATT:
                return WebOrder::createOneFromMinibatt($orderApi);
                
            case IntegrationChannel::CHANNEL_FITBITCORPORATE:
                return WebOrder::createOneFromFitbitCorporate($orderApi);

            case IntegrationChannel::CHANNEL_MANOMANO_DE:
                $webOrder = WebOrder::createOneFromManoMano($orderApi);
                $webOrder->setChannel(IntegrationChannel::CHANNEL_MANOMANO_DE);
                $webOrder->setSubchannel('Manomano.de');
                $webOrder->setCarrierService(WebOrder::CARRIER_UPS);
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


    public static function createOneFromMinibatt($orderApi): WebOrder
    {
        $webOrder = WebOrder::createOrderFromShopify($orderApi);
        $webOrder->setExternalNumber('MNB-' . $orderApi['order_number']);
        $webOrder->setChannel(IntegrationChannel::CHANNEL_MINIBATT);
        $webOrder->setSubchannel('Minibatt.com');
        $webOrder->addLog('Retrieved from Minibatt.com');
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
        $webOrder->setStatus(WebOrder::STATE_CREATED);
        $webOrder->setErpDocument(WebOrder::DOCUMENT_ORDER);
        $webOrder->setWarehouse(WebOrder::DEPOT_LAROCA);
        $webOrder->setFulfilledBy(WebOrder::FULFILLED_BY_SELLER);
        $webOrder->setCarrierService(WebOrder::CARRIER_DHL);
        $webOrder->setContent($orderApi);
        return $webOrder;
    }



    



    public static function createOneFromAliExpress($orderApi): WebOrder
    {
        $webOrder = new WebOrder();
        $webOrder->setExternalNumber($orderApi->id);
        $webOrder->setStatus(WebOrder::STATE_CREATED);
        $webOrder->setChannel(IntegrationChannel::CHANNEL_ALIEXPRESS);
        $webOrder->setSubchannel('AliExpress');
        $webOrder->setErpDocument(WebOrder::DOCUMENT_ORDER);
        $datePurchase = DatetimeUtils::createDateTimeFromDateWithDelay($orderApi->gmt_pay_success);
        $webOrder->setPurchaseDate($datePurchase);
        $webOrder->setWarehouse(WebOrder::DEPOT_LAROCA);
        $webOrder->setFulfilledBy(WebOrder::FULFILLED_BY_SELLER);
        $webOrder->setCarrierService(WebOrder::CARRIER_DHL);
        $webOrder->addLog('Retrieved from Aliexpress');
        $webOrder->setContent($orderApi);
        return $webOrder;
    }




    public static function createOneFromArise($orderApi): WebOrder
    {
        $webOrder = new WebOrder();
        $webOrder->setExternalNumber($orderApi->order_id);
        $webOrder->setStatus(WebOrder::STATE_CREATED);
        $webOrder->setChannel(IntegrationChannel::CHANNEL_ARISE);
        $webOrder->setSubchannel('Miravia.es');
        $webOrder->setErpDocument(WebOrder::DOCUMENT_ORDER);
        $datePurchase = new DateTime($orderApi->created_at, new DateTimeZone('Europe/London'));
        $datePurchase->setTimezone(new DateTimeZone('Europe/Paris'));
        $webOrder->setPurchaseDate($datePurchase);
        $webOrder->setWarehouse(WebOrder::DEPOT_LAROCA);
        $webOrder->setFulfilledBy(WebOrder::FULFILLED_BY_SELLER);
        
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



    public static function createOrderFromMirakl($orderApi): WebOrder
    {
        $webOrder = new WebOrder();
        $webOrder->setPurchaseDate(DatetimeUtils::transformFromIso8601($orderApi['created_date']));
        $webOrder->setStatus(WebOrder::STATE_CREATED);
        $webOrder->setErpDocument(WebOrder::DOCUMENT_ORDER);
        $webOrder->setWarehouse(WebOrder::DEPOT_LAROCA);
        $webOrder->setFulfilledBy(WebOrder::FULFILLED_BY_SELLER);
        $webOrder->setCarrierService(WebOrder::CARRIER_DHL);
        $webOrder->setContent($orderApi);
        
        
        $webOrder->setExternalNumber($orderApi['id']);
        
        $skus = [];
        foreach ($orderApi["order_lines"] as $line) {
            $skus[] = $line['offer']['sku'];
        }
        $shouldBeSentByUps = UpsGetTracking::shouldBeSentWith($skus);
        if ($shouldBeSentByUps) {
            $webOrder->setCarrierService(WebOrder::CARRIER_UPS);
        }

        if($orderApi['customer']['shipping_address']["country"]=='DE') {
            $webOrder->setCarrierService(WebOrder::CARRIER_UPS);
        }
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
        $webOrder->setStatus(WebOrder::STATE_CREATED);
        $webOrder->setErpDocument(WebOrder::DOCUMENT_ORDER);
        $webOrder->setWarehouse(WebOrder::DEPOT_LAROCA);
        $webOrder->setFulfilledBy(WebOrder::FULFILLED_BY_SELLER);
        $webOrder->setCarrierService(WebOrder::CARRIER_DHL);
        $webOrder->setContent($orderApi);
        $webOrder->setExternalNumber($orderApi['order_reference']);
        
        $skus = [];
        foreach ($orderApi["products"] as $line) {
            $skus[] = $line['seller_sku'];
        }
        $shouldBeSentByUps = UpsGetTracking::shouldBeSentWith($skus);
        if ($shouldBeSentByUps) {
            $webOrder->setCarrierService(WebOrder::CARRIER_UPS);
        }
        
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
        if (in_array($this->channel, [
            IntegrationChannel::CHANNEL_OWLETCARE,
            IntegrationChannel::CHANNEL_FLASHLED,
            IntegrationChannel::CHANNEL_MINIBATT,
            IntegrationChannel::CHANNEL_FITBITCORPORATE,
            IntegrationChannel::CHANNEL_DECATHLON,
            IntegrationChannel::CHANNEL_LEROYMERLIN,
            IntegrationChannel::CHANNEL_BOULANGER,
            IntegrationChannel::CHANNEL_MANOMANO_ES,
            IntegrationChannel::CHANNEL_MANOMANO_DE,
            IntegrationChannel::CHANNEL_MANOMANO_IT,
            IntegrationChannel::CHANNEL_MANOMANO_FR,
        ])) {
            return $this->getContent();
        }
        return json_decode(json_encode($this->getContent()));
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
