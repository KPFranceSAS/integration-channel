<?php

namespace App\Entity;

use App\Repository\WebOrderRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use stdClass;

/**
 * @ORM\Entity(repositoryClass=WebOrderRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class WebOrder
{
    const  CHANNEL_CHANNELADVISOR = 'CHANNELADVISOR';

    const DOCUMENT_ORDER = 'ORDER';

    const DOCUMENT_INVOICE = 'INVOICE';

    const DEPOT_FBA_AMAZON = 'AMAZON';

    const DEPOT_CENTRAL = 'CENTRAL';

    const DEPOT_ACTIVE_ANTS = 'ACTIVE';

    const DEPOT_MIXED = 'MIXED';

    const STATE_ERROR_INVOICE = -2;

    const STATE_ERROR = -1;

    const STATE_CREATED = 0;

    const STATE_SYNC_TO_ERP = 1;

    const STATE_INVOICED = 5;


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


    public function haveInvoice()
    {
        return $this->invoiceErp != null;
    }

    public function needRetry()
    {
        return $this->status == self::STATE_ERROR;
    }


    public function getOrderErrors()
    {
        foreach ($this->errors as $error) {
            return $error['content'];
        }
        return '';
    }




    public function documentInErp()
    {
        if ($this->erpDocument && $this->status > 0) {
            return $this->erpDocument == self::DOCUMENT_INVOICE ?  $this->invoiceErp : $this->orderErp;
        }
        return '-';
    }



    /**
     * Undocumented function
     *
     * @param stdClass $orderApi
     * @return WebOrder
     */
    public static function createOneFromChannelAdvisor(stdClass $orderApi)
    {
        $webOrder = new WebOrder();
        $webOrder->setExternalNumber($orderApi->SiteOrderID);
        $webOrder->setStatus(WebOrder::STATE_CREATED);
        $webOrder->setChannel(WebOrder::CHANNEL_CHANNELADVISOR);
        $webOrder->setSubchannel($orderApi->SiteName);
        $webOrder->setErpDocument(WebOrder::DOCUMENT_ORDER);

        if ($orderApi->DistributionCenterTypeRollup == 'ExternallyManaged') {
            $webOrder->setWarehouse(WebOrder::DEPOT_FBA_AMAZON);
        } elseif ($orderApi->DistributionCenterTypeRollup == 'SellerManaged') {
            $webOrder->setWarehouse(WebOrder::DEPOT_ACTIVE_ANTS);
        } else {
            $webOrder->setWarehouse(WebOrder::DEPOT_MIXED);
        }
        $webOrder->addLog('Retrieved from ChannelAdvisorApi');
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
        return json_decode(json_encode($this->getContent()));
    }


    public $orderBCContent = [];


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
}
