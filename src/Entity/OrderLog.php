<?php

namespace App\Entity;

use App\Helper\Traits\TraitTimeUpdated;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class OrderLog
{
    use TraitTimeUpdated;

    final public const CATEGORY_ERP = 'Business central';

   
    final public const CATEGORY_SYSTEM = 'Third party system';

    final public const CATEGORY_SKU = 'Sku correlation';

    final public const CATEGORY_DELAY_SHIPMENT_CREATION = 'Shipment creation';

    final public const CATEGORY_DELAY_SHIPPING = 'Shipping delay';

    final public const CATEGORY_DELAY_DELIVERY = 'Delivery delay';

    final public const CATEGORY_DELAY_INVOICE = 'Invoice delay';

    final public const CATEGORY_LENGTH = 'Address length';

    final public const CATEGORY_OTHERS = 'Others';


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $orderNumber = null;

    #[ORM\Column(type: 'integer')]
    private ?int $orderId = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $logDate = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $unicityHash = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $integrationChannel = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $marketplace = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): self
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    public function setOrderId(int $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getLogDate(): ?\DateTimeInterface
    {
        return $this->logDate;
    }

    public function setLogDate(\DateTimeInterface $logDate): self
    {
        $this->logDate = $logDate;

        return $this;
    }

    public function getUnicityHash(): ?string
    {
        return $this->unicityHash;
    }

    public function setUnicityHash(string $unicityHash): self
    {
        $this->unicityHash = $unicityHash;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getIntegrationChannel(): ?string
    {
        return $this->integrationChannel;
    }

    public function setIntegrationChannel(string $integrationChannel): self
    {
        $this->integrationChannel = $integrationChannel;

        return $this;
    }

    public function getMarketplace(): ?string
    {
        return $this->marketplace;
    }

    public function setMarketplace(string $marketplace): self
    {
        $this->marketplace = $marketplace;

        return $this;
    }
}
