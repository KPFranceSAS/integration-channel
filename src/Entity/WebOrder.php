<?php

namespace App\Entity;

use App\Helper\Utils\DatetimeUtils;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class WebOrder
{
    const CHANNEL_CHANNELADVISOR = 'CHANNELADVISOR';

    const CHANNEL_ALIEXPRESS = 'ALIEXPRESS';

    const CHANNEL_OWLETCARE = 'OWLETCARE';

    const DOCUMENT_ORDER = 'ORDER';

    const DOCUMENT_INVOICE = 'INVOICE';


    const DEPOT_FBA_AMAZON = 'AMAZON';

    const DEPOT_CENTRAL = 'CENTRAL';

    const DEPOT_ACTIVE_ANTS = 'ACTIVE';

    const DEPOT_MADRID = 'MADRID';

    const DEPOT_MIXED = 'MIXED';

    const TIMING_INTEGRATION = 24;

    const TIMING_SHIPPING = 72;

    const FULFILLED_BY_EXTERNAL = 'EXTERNALLY MANAGED';

    const FULFILLED_BY_SELLER = 'OWN MANAGED';

    const FULFILLED_MIXED = 'MIXED MANAGED';

    const STATE_ERROR_INVOICE = -2;

    const STATE_ERROR = -1;

    const STATE_CREATED = 0;

    const STATE_SYNC_TO_ERP = 1;

    const STATE_INVOICED = 5;

    const STATE_CANCELLED = 7;


    const STATE_ERROR_INVOICE_TEXT = 'Error send invoice';

    const STATE_ERROR_TEXT = 'Error integration';

    const STATE_CREATED_TEXT = 'Order integrated';

    const STATE_SYNC_TO_ERP_TEXT = 'Order integrated';

    const STATE_SYNC_TO_WAITING_DELIVERY_TEXT = 'Waiting for delivery';

    const STATE_INVOICED_TEXT = 'Invoice integrated';

    const STATE_UNDEFINED_TEXT = 'Undefined';

    const STATE_CANCELLED_TEXT = "Cancelled";


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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $orderErp;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $invoiceErp;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $errors = [];

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $logs = [];

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

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

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fulfilledBy;

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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


    public function haveInvoice()
    {
        return $this->invoiceErp != null;
    }

    public function needRetry()
    {
        return in_array($this->status,  [self::STATE_ERROR, self::STATE_ERROR_INVOICE]);
    }


    public function getStatusLitteral()
    {
        if ($this->status ==  self::STATE_ERROR) {
            return self::STATE_ERROR_TEXT;
        } else if ($this->status ==  self::STATE_SYNC_TO_ERP) {
            return $this->fulfilledBy == self::FULFILLED_BY_SELLER ? self::STATE_SYNC_TO_WAITING_DELIVERY_TEXT : self::STATE_SYNC_TO_ERP_TEXT;
        } else if ($this->status ==  self::STATE_INVOICED) {
            return self::STATE_INVOICED_TEXT;
        } else if ($this->status ==  self::STATE_CANCELLED) {
            return self::STATE_CANCELLED_TEXT;
        } else if ($this->status ==  self::STATE_ERROR_INVOICE) {
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




    public function hasDelayTreatment()
    {
        if ($this->channel == self::CHANNEL_CHANNELADVISOR && $this->fulfilledBy == self::FULFILLED_BY_EXTERNAL && $this->status != self::STATE_INVOICED) {
            $delay = $this->getNbHoursSinceCreation();
            return $delay >= self::TIMING_INTEGRATION;
        } elseif ($this->fulfilledBy == self::FULFILLED_BY_SELLER && $this->status != self::STATE_INVOICED) {
            $delay = $this->getNbHoursSincePurchaseDate();
            return $delay >= self::TIMING_SHIPPING;
        }
        return false;
    }



    public function getDelayProblemMessage()
    {
        if ($this->channel == self::CHANNEL_CHANNELADVISOR && $this->fulfilledBy == self::FULFILLED_BY_EXTERNAL && $this->status != self::STATE_INVOICED) {
            $delay = $this->getNbHoursSinceCreation();
            return 'Invoice integration should be done in ' . self::TIMING_INTEGRATION . ' hours  for ' . $this->__toString();
        } elseif ($this->fulfilledBy == self::FULFILLED_BY_SELLER && $this->status != self::STATE_INVOICED) {
            $delay = $this->getNbHoursSincePurchaseDate();
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
            case  WebOrder::CHANNEL_ALIEXPRESS:
                return 'https://gsp.aliexpress.com/apps/order/detail?orderId=' . $this->externalNumber;
            case  WebOrder::CHANNEL_CHANNELADVISOR:
                return 'https://sellercentral.amazon.fr/orders-v3/order/' . $this->externalNumber;
            case  WebOrder::CHANNEL_OWLETCARE:
                $order = $this->getOrderContent();
                return 'https://owlet-spain.myshopify.com/admin/orders/' . $order['id'];
        }
        throw new Exception('No url link of weborder for ' . $this->channel);
    }

    public static function createOneFromChannelAdvisor($orderApi)
    {
        $webOrder = new WebOrder();
        $webOrder->setExternalNumber($orderApi->SiteOrderID);
        $webOrder->setStatus(WebOrder::STATE_CREATED);
        $webOrder->setChannel(WebOrder::CHANNEL_CHANNELADVISOR);
        $webOrder->setSubchannel($orderApi->SiteName);
        $webOrder->setErpDocument(WebOrder::DOCUMENT_ORDER);
        $webOrder->setPurchaseDateFromString($orderApi->PaymentDateUtc);

        if ($orderApi->DistributionCenterTypeRollup == 'ExternallyManaged') {
            $webOrder->setWarehouse(WebOrder::DEPOT_FBA_AMAZON);
            $webOrder->setFulfilledBy(WebOrder::FULFILLED_BY_EXTERNAL);
        } elseif ($orderApi->DistributionCenterTypeRollup == 'SellerManaged') {
            $webOrder->setWarehouse(WebOrder::DEPOT_CENTRAL);
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



    public static function createOneFrom($orderApi, $channel)
    {
        if ($channel == WebOrder::CHANNEL_ALIEXPRESS) {
            return WebOrder::createOneFromAliExpress($orderApi);
        } else if ($channel == WebOrder::CHANNEL_CHANNELADVISOR) {
            return WebOrder::createOneFromChannelAdvisor($orderApi);
        } else if ($channel == WebOrder::CHANNEL_OWLETCARE) {
            return WebOrder::createOneFromOwletcare($orderApi);
        }
        throw new Exception('No constructor of weborder for ' . $channel);
    }


    public static function createOneFromOwletcare($orderApi)
    {
        $webOrder = new WebOrder();
        $webOrder->setExternalNumber((string)$orderApi['order_number']);
        $webOrder->setPurchaseDate(DatetimeUtils::transformFromIso8601($orderApi['processed_at']));
        $webOrder->setStatus(WebOrder::STATE_CREATED);
        $webOrder->setChannel(WebOrder::CHANNEL_OWLETCARE);
        $webOrder->setSubchannel('Owletcare');
        $webOrder->setErpDocument(WebOrder::DOCUMENT_ORDER);
        $webOrder->setWarehouse(WebOrder::DEPOT_CENTRAL);
        $webOrder->setFulfilledBy(WebOrder::FULFILLED_BY_SELLER);
        $webOrder->addLog('Retrieved from owletcare.es');
        $webOrder->setContent($orderApi);
        return $webOrder;
    }




    public static function createOneFromAliExpress($orderApi)
    {
        $webOrder = new WebOrder();
        $webOrder->setExternalNumber($orderApi->id);
        $webOrder->setStatus(WebOrder::STATE_CREATED);
        $webOrder->setChannel(WebOrder::CHANNEL_ALIEXPRESS);
        $webOrder->setSubchannel('AliExpress');
        $webOrder->setErpDocument(WebOrder::DOCUMENT_ORDER);
        $datePurchase = DateTime::createFromFormat('Y-m-d H:i:s', $orderApi->gmt_pay_success);
        $webOrder->setPurchaseDate($datePurchase);
        $webOrder->setWarehouse(WebOrder::DEPOT_CENTRAL);
        $webOrder->setFulfilledBy(WebOrder::FULFILLED_BY_SELLER);
        $webOrder->addLog('Retrieved from Aliexpress');
        $webOrder->setContent($orderApi);
        return $webOrder;
    }

    public function haveNoLogWithMessage($logMessage)
    {
        foreach ($this->logs as $log) {
            if ($log['content'] == $logMessage) {
                return false;
            }
        }
        return true;
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


    /**
     * Undocumented function
     *
     * @param string $content
     * @param string $level
     * @return void
     */
    public function addLog($content, $level = 'info')
    {
        $this->logs[] = [
            'date' => date('d-m-Y H:i:s'),
            'content' => $content,
            'level' => $level
        ];
    }


    public function cleanErrors(): self
    {
        $this->errors = [];
        return $this;
    }


    public function getOrderContent()
    {
        if ($this->channel == self::CHANNEL_OWLETCARE) {
            return $this->getContent();
        }

        return json_decode(json_encode($this->getContent()));
    }


    public $orderBCContent = [];

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $purchaseDate;



    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getLogs(): ?array
    {
        return $this->logs;
    }

    public function setLogs(?array $logs): self
    {
        $this->logs = $logs;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

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

    public function getPurchaseDate(): ?\DateTimeInterface
    {
        return $this->purchaseDate;
    }

    public function setPurchaseDate(?\DateTimeInterface $purchaseDate): self
    {
        $this->purchaseDate = $purchaseDate;

        return $this;
    }
}
