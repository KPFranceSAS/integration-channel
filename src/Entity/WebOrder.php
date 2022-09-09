<?php

namespace App\Entity;

use App\Helper\Traits\TraitLoggable;
use App\Helper\Traits\TraitTimeUpdated;
use App\Helper\Utils\DatetimeUtils;
use DateTime;
use DateTimeInterface;
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

    public const CHANNEL_CHANNELADVISOR = 'CHANNELADVISOR';
    public const  CHANNEL_ALIEXPRESS = 'ALIEXPRESS';
    public const  CHANNEL_FITBITEXPRESS = 'FITBITEXPRESS';
    public const  CHANNEL_OWLETCARE = 'OWLETCARE';
    public const  CHANNEL_MINIBATT = 'MINIBATT';
    public const  CHANNEL_FLASHLED = 'FLASHLED';
    public const  CHANNEL_FITBITCORPORATE = 'FITBITCORPORATE';
    public const  DOCUMENT_ORDER = 'ORDER';
    public const  DOCUMENT_INVOICE = 'INVOICE';

    public const  DEPOT_FBA_AMAZON = 'AMAZON';
    public const  DEPOT_CENTRAL = 'CENTRAL';
    public const  DEPOT_LAROCA = 'LAROCA';
    public const  DEPOT_ACTIVE_ANTS = 'ACTIVE';
    public const  DEPOT_MADRID = 'MADRID';
    public const  DEPOT_MIXED = 'MIXED';

    public const  TIMING_INTEGRATION = 24;
    public const  TIMING_SHIPPING = 30;

    public const  FULFILLED_BY_EXTERNAL = 'EXTERNALLY MANAGED';
    public const  FULFILLED_BY_SELLER = 'OWN MANAGED';
    public const  FULFILLED_MIXED = 'MIXED MANAGED';

    public const  STATE_ERROR_INVOICE = -2;
    public const  STATE_ERROR = -1;
    public const  STATE_CREATED = 0;
    public const  STATE_SYNC_TO_ERP = 1;
    public const  STATE_INVOICED = 5;
    public const  STATE_CANCELLED = 7;

    public const  STATE_ERROR_INVOICE_TEXT = 'Error send invoice';
    public const  STATE_ERROR_TEXT = 'Error integration';
    public const  STATE_CREATED_TEXT = 'Order integrated';
    public const  STATE_SYNC_TO_ERP_TEXT = 'Order integrated';
    public const  STATE_SYNC_TO_WAITING_DELIVERY_TEXT = 'Wait for shipping';
    public const  STATE_INVOICED_TEXT = 'Invoice integrated';
    public const  STATE_UNDEFINED_TEXT = 'Undefined';
    public const  STATE_CANCELLED_TEXT = "Cancelled";


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


    public $amzEvents;



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




    public function getStatusLitteral()
    {
        if ($this->status ==  self::STATE_ERROR) {
            return self::STATE_ERROR_TEXT;
        } elseif ($this->status ==  self::STATE_SYNC_TO_ERP) {
            return $this->fulfilledBy == self::FULFILLED_BY_SELLER
                ? self::STATE_SYNC_TO_WAITING_DELIVERY_TEXT
                : self::STATE_SYNC_TO_ERP_TEXT;
        } elseif ($this->status ==  self::STATE_INVOICED) {
            return self::STATE_INVOICED_TEXT;
        } elseif ($this->status ==  self::STATE_CANCELLED) {
            return self::STATE_CANCELLED_TEXT;
        } elseif ($this->status ==  self::STATE_ERROR_INVOICE) {
            return self::STATE_ERROR_INVOICE_TEXT;
        } else {
            return self::STATE_UNDEFINED_TEXT;
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
        if (
            $this->channel == self::CHANNEL_CHANNELADVISOR
            && $this->fulfilledBy == self::FULFILLED_BY_EXTERNAL
            && $this->status != self::STATE_INVOICED
        ) {
            return DatetimeUtils::isOutOfDelay($this->createdAt, self::TIMING_INTEGRATION);
        } elseif ($this->fulfilledBy == self::FULFILLED_BY_SELLER && $this->status != self::STATE_INVOICED) {
            return DatetimeUtils::isOutOfDelayBusinessDays($this->purchaseDate, self::TIMING_SHIPPING);
        }
        return false;
    }

    public function getDelayProblemMessage()
    {
        if (
            $this->channel == self::CHANNEL_CHANNELADVISOR
            && $this->fulfilledBy == self::FULFILLED_BY_EXTERNAL && $this->status != self::STATE_INVOICED
        ) {
            return 'Invoice should be done in ' . self::TIMING_INTEGRATION . ' hours  for ' . $this->__toString();
        } elseif ($this->fulfilledBy == self::FULFILLED_BY_SELLER && $this->status != self::STATE_INVOICED) {
            return 'Shipping should be processed in ' . self::TIMING_SHIPPING . ' hours  for ' . $this->__toString();
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
        switch ($this->channel) {
            case WebOrder::CHANNEL_FITBITEXPRESS:
            case WebOrder::CHANNEL_ALIEXPRESS:
                return 'https://gsp.aliexpress.com/apps/order/detail?orderId=' . $this->externalNumber;
            case WebOrder::CHANNEL_CHANNELADVISOR:
                return 'https://sellercentral.amazon.fr/orders-v3/order/' . $this->externalNumber;
            case WebOrder::CHANNEL_OWLETCARE:
                $order = $this->getOrderContent();
                return 'https://owlet-spain.myshopify.com/admin/orders/' . $order['id'];
            case WebOrder::CHANNEL_MINIBATT:
                $order = $this->getOrderContent();
                return 'https://minibattstore.myshopify.com/admin/orders/' . $order['id'];
            case WebOrder::CHANNEL_FLASHLED:
                $order = $this->getOrderContent();
                return 'https://testflashled.myshopify.com/admin/orders/' . $order['id'];
            case WebOrder::CHANNEL_FITBITCORPORATE:
                $order = $this->getOrderContent();
                return 'https://fitbitcorporate.myshopify.com/admin/orders/' . $order['id'];
        }
        throw new Exception('No url link of weborder for ' . $this->channel);
    }

    public static function createOneFromChannelAdvisor($orderApi): WebOrder
    {
        $webOrder = new WebOrder();
        $webOrder->setExternalNumber($orderApi->SiteOrderID);
        $webOrder->setStatus(WebOrder::STATE_CREATED);
        $webOrder->setChannel(WebOrder::CHANNEL_CHANNELADVISOR);
        $webOrder->setSubchannel($orderApi->SiteName);
        $webOrder->setErpDocument(WebOrder::DOCUMENT_ORDER);
        $webOrder->setPurchaseDateFromString($orderApi->CreatedDateUtc);

        if ($orderApi->DistributionCenterTypeRollup == 'ExternallyManaged') {
            $webOrder->setWarehouse(WebOrder::DEPOT_FBA_AMAZON);
            $webOrder->setFulfilledBy(WebOrder::FULFILLED_BY_EXTERNAL);
        } elseif ($orderApi->DistributionCenterTypeRollup == 'SellerManaged') {
            $webOrder->setWarehouse(WebOrder::DEPOT_LAROCA);
            $webOrder->setFulfilledBy(WebOrder::FULFILLED_BY_SELLER);
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
            case  WebOrder::CHANNEL_ALIEXPRESS:
                return WebOrder::createOneFromAliExpress($orderApi);
            case   WebOrder::CHANNEL_ALIEXPRESS:
                return WebOrder::createOneFromAliExpress($orderApi);
            case   WebOrder::CHANNEL_FITBITEXPRESS:
                $webOrder = WebOrder::createOneFromAliExpress($orderApi);
                $webOrder->setChannel(WebOrder::CHANNEL_FITBITEXPRESS);
                return $webOrder;
            case   WebOrder::CHANNEL_CHANNELADVISOR:
                return WebOrder::createOneFromChannelAdvisor($orderApi);
            case   WebOrder::CHANNEL_OWLETCARE:
                return WebOrder::createOneFromOwletcare($orderApi);
            case   WebOrder::CHANNEL_FLASHLED:
                return WebOrder::createOneFromFlashled($orderApi);
            case   WebOrder::CHANNEL_MINIBATT:
                return WebOrder::createOneFromMinibatt($orderApi);
            case   WebOrder::CHANNEL_FITBITCORPORATE:
                return WebOrder::createOneFromFitbitCorporate($orderApi);
        }

        throw new Exception('No constructor of weborder for ' . $channel);
    }


    public static function createOneFromOwletcare($orderApi): WebOrder
    {
        $webOrder = WebOrder::createOrderFromShopify($orderApi);
        $webOrder->setExternalNumber('OWL-' . $orderApi['order_number']);
        $webOrder->setChannel(WebOrder::CHANNEL_OWLETCARE);
        $webOrder->setSubchannel('Owletbaby.es');
        $webOrder->addLog('Retrieved from Owletbaby.es');
        return $webOrder;
    }


    public static function createOneFromMinibatt($orderApi): WebOrder
    {
        $webOrder = WebOrder::createOrderFromShopify($orderApi);
        $webOrder->setExternalNumber('MNB-' . $orderApi['order_number']);
        $webOrder->setChannel(WebOrder::CHANNEL_MINIBATT);
        $webOrder->setSubchannel('Minibatt.com');
        $webOrder->addLog('Retrieved from Minibatt.com');
        return $webOrder;
    }


    public static function createOneFromFitbitCorporate($orderApi): WebOrder
    {
        $webOrder = WebOrder::createOrderFromShopify($orderApi);
        $webOrder->setExternalNumber('FBT-' . $orderApi['order_number']);
        $webOrder->setChannel(WebOrder::CHANNEL_FITBITCORPORATE);
        $webOrder->setSubchannel('Google.kps.direct');
        $webOrder->addLog('Retrieved from Google.kps.direct');
        return $webOrder;
    }


    public static function createOneFromFlashled($orderApi): WebOrder
    {
        $webOrder = WebOrder::createOrderFromShopify($orderApi);
        $webOrder->setExternalNumber('FLS-' . $orderApi['order_number']);
        $webOrder->setChannel(WebOrder::CHANNEL_FLASHLED);
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
        $webOrder->setContent($orderApi);
        return $webOrder;
    }




    public static function createOneFromAliExpress($orderApi): WebOrder
    {
        $webOrder = new WebOrder();
        $webOrder->setExternalNumber($orderApi->id);
        $webOrder->setStatus(WebOrder::STATE_CREATED);
        $webOrder->setChannel(WebOrder::CHANNEL_ALIEXPRESS);
        $webOrder->setSubchannel('AliExpress');
        $webOrder->setErpDocument(WebOrder::DOCUMENT_ORDER);
        $datePurchase = DatetimeUtils::createDateTimeFromAliExpressDate($orderApi->gmt_pay_success);
        $webOrder->setPurchaseDate($datePurchase);
        $webOrder->setWarehouse(WebOrder::DEPOT_LAROCA);
        $webOrder->setFulfilledBy(WebOrder::FULFILLED_BY_SELLER);
        $webOrder->addLog('Retrieved from Aliexpress');
        $webOrder->setContent($orderApi);
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
            self::CHANNEL_OWLETCARE,
            self::CHANNEL_FLASHLED,
            self::CHANNEL_MINIBATT,
            self::CHANNEL_FITBITCORPORATE,
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
}
