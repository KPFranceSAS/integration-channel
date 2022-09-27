<?php

namespace App\Entity;

use App\Helper\Traits\TraitTimeUpdated;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class ProductSaleChannelHistory
{

    
    public const TYPE_CREATION = 0;
    public const TYPE_ACTIVATION = 1;

    public const TYPE_DESACTIVATION = 2;

    public const TYPE_MODIFICATION_REGULAR_PRICE = 3;
    
    public const TYPE_MODIFICATION_SALE_PRICE = 4;

    public const TYPE_ACTIVATION_PROMOTION = 5;

    public const TYPE_DESACTIVATION_PROMOTION = 6;

    use TraitTimeUpdated;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $price;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $typeModification;

    /**
     * @ORM\ManyToOne(targetEntity=ProductSaleChannel::class, inversedBy="productSaleChannelHistories")
     * @ORM\JoinColumn(nullable=false)
     */
    private $productSaleChannel;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $regularPrice;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $promotionPrice;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getTypeModification(): ?int
    {
        return $this->typeModification;
    }

    public function setTypeModification(?int $typeModification): self
    {
        $this->typeModification = $typeModification;

        return $this;
    }

    public function getProductSaleChannel(): ?ProductSaleChannel
    {
        return $this->productSaleChannel;
    }

    public function setProductSaleChannel(?ProductSaleChannel $productSaleChannel): self
    {
        $this->productSaleChannel = $productSaleChannel;

        return $this;
    }

    public function getRegularPrice(): ?float
    {
        return $this->regularPrice;
    }

    public function setRegularPrice(?float $regularPrice): self
    {
        $this->regularPrice = $regularPrice;

        return $this;
    }

    public function getPromotionPrice(): ?float
    {
        return $this->promotionPrice;
    }

    public function setPromotionPrice(?float $promotionPrice): self
    {
        $this->promotionPrice = $promotionPrice;

        return $this;
    }
}
