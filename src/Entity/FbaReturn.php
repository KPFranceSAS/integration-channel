<?php

namespace App\Entity;

use App\Repository\FbaReturnRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class FbaReturn
{

    const LOCALIZATION_FBA = 'FBA';

    const LOCALIZATION_BIARRITZ = 'BIARRITZ';

    const LOCALIZATION_LAROCA = 'LAROCA';


    const STATUS_CREATED = 0;

    const STATUS_WAITING_CUSTOMER = 1;

    const STATUS_RETURN_TO_FBA_NOTSELLABLE = 2;

    const STATUS_RETURN_TO_SALE = 3;

    const STATUS_RETURN_TO_BIARRITZ = 4;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $amazonOrderId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sellerOrderId;


    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $adjustmentId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $sku;

    /**
     * @ORM\Column(type="date_immutable")
     */
    private $postedDate;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $logs = [];

    /**
     * @ORM\ManyToOne(targetEntity=AmazonRemovalOrder::class, inversedBy="fbaReturns")
     */
    private $amazonRemoval;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lpn;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class)
     */
    private $product;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $marketplaceName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $localization;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $amzProductStatus;


    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }


    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }


    public function addLog($content, $level = 'info', $user = null)
    {
        $this->logs[] = [
            'date' => date('d-m-Y H:i:s'),
            'content' => $content,
            'level' => $level,
            'user' => $user
        ];
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
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

    public function getAmazonOrderId(): ?string
    {
        return $this->amazonOrderId;
    }

    public function setAmazonOrderId(?string $amazonOrderId): self
    {
        $this->amazonOrderId = $amazonOrderId;

        return $this;
    }

    public function getSellerOrderId(): ?string
    {
        return $this->sellerOrderId;
    }

    public function setSellerOrderId(?string $sellerOrderId): self
    {
        $this->sellerOrderId = $sellerOrderId;

        return $this;
    }

    public function getAdjustmentId(): ?string
    {
        return $this->adjustmentId;
    }

    public function setAdjustmentId(?string $adjustmentId): self
    {
        $this->adjustmentId = $adjustmentId;

        return $this;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(string $sku): self
    {
        $this->sku = $sku;

        return $this;
    }

    public function getPostedDate(): ?\DateTimeImmutable
    {
        return $this->postedDate;
    }

    public function setPostedDate(\DateTimeImmutable $postedDate): self
    {
        $this->postedDate = $postedDate;

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

    public function getAmazonRemoval(): ?AmazonRemovalOrder
    {
        return $this->amazonRemoval;
    }

    public function setAmazonRemoval(?AmazonRemovalOrder $amazonRemoval): self
    {
        $this->amazonRemoval = $amazonRemoval;

        return $this;
    }

    public function getLpn(): ?string
    {
        return $this->lpn;
    }

    public function setLpn(?string $lpn): self
    {
        $this->lpn = $lpn;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getMarketplaceName(): ?string
    {
        return $this->marketplaceName;
    }

    public function setMarketplaceName(?string $marketplaceName): self
    {
        $this->marketplaceName = $marketplaceName;

        return $this;
    }

    public function getLocalization(): ?string
    {
        return $this->localization;
    }

    public function setLocalization(?string $localization): self
    {
        $this->localization = $localization;

        return $this;
    }

    public function getAmzProductStatus(): ?string
    {
        return $this->amzProductStatus;
    }

    public function setAmzProductStatus(?string $amzProductStatus): self
    {
        $this->amzProductStatus = $amzProductStatus;

        return $this;
    }
}
