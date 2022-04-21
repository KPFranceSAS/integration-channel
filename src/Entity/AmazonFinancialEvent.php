<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class AmazonFinancialEvent
{
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
     * @ORM\ManyToOne(targetEntity=AmazonFinancialEventGroup::class, inversedBy="amazonFinancialEvents")
     * @ORM\JoinColumn(nullable=false)
     */
    private $eventGroup;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $transactionType;

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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $shipmentId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $marketplaceName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $amountType;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $amountDescription;

    /**
     * @ORM\Column(type="float")
     */
    private $amount;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $amountCurrency;

    /**
     * @ORM\Column(type="datetime")
     */
    private $postedDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $orderItemCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sku;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class)
     */
    private $product;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $qtyPurchased;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $promotionId;

    public function getId(): ?int
    {
        return $this->id;
    }


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


    public function getLitteralPrice(): string
    {
        return ($this->amountCurrency != $this->amount) ? $this->amountCurrency . ' GBP' : $this->amountCurrency . ' EUR';
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

    public function getEventGroup(): ?AmazonFinancialEventGroup
    {
        return $this->eventGroup;
    }

    public function setEventGroup(?AmazonFinancialEventGroup $eventGroup): self
    {
        $this->eventGroup = $eventGroup;

        return $this;
    }

    public function getTransactionType(): ?string
    {
        return $this->transactionType;
    }

    public function setTransactionType(string $transactionType): self
    {
        $this->transactionType = $transactionType;

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

    public function getShipmentId(): ?string
    {
        return $this->shipmentId;
    }

    public function setShipmentId(?string $shipmentId): self
    {
        $this->shipmentId = $shipmentId;

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

    public function getAmountType(): ?string
    {
        return $this->amountType;
    }

    public function setAmountType(?string $amountType): self
    {
        $this->amountType = $amountType;

        return $this;
    }

    public function getAmountDescription(): ?string
    {
        return $this->amountDescription;
    }

    public function setAmountDescription(?string $amountDescription): self
    {
        $this->amountDescription = $amountDescription;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmountCurrency(): ?float
    {
        return $this->amountCurrency;
    }

    public function setAmountCurrency(?float $amountCurrency): self
    {
        $this->amountCurrency = $amountCurrency;

        return $this;
    }

    public function getPostedDate(): ?\DateTimeInterface
    {
        return $this->postedDate;
    }

    public function setPostedDate(\DateTimeInterface $postedDate): self
    {
        $this->postedDate = $postedDate;

        return $this;
    }

    public function getOrderItemCode(): ?string
    {
        return $this->orderItemCode;
    }

    public function setOrderItemCode(?string $orderItemCode): self
    {
        $this->orderItemCode = $orderItemCode;

        return $this;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): self
    {
        $this->sku = $sku;

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

    public function getQtyPurchased(): ?int
    {
        return $this->qtyPurchased;
    }

    public function setQtyPurchased(?int $qtyPurchased): self
    {
        $this->qtyPurchased = $qtyPurchased;

        return $this;
    }

    public function getPromotionId(): ?string
    {
        return $this->promotionId;
    }

    public function setPromotionId(?string $promotionId): self
    {
        $this->promotionId = $promotionId;

        return $this;
    }
}
