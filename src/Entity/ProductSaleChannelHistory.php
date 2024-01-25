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
    final public const TYPE_CREATION = 0;
    final public const TYPE_ACTIVATION = 1;

    final public const TYPE_DESACTIVATION = 2;

    final public const TYPE_MODIFICATION_REGULAR_PRICE = 3;
    
    final public const TYPE_MODIFICATION_SALE_PRICE = 4;

    final public const TYPE_ACTIVATION_PROMOTION = 5;

    final public const TYPE_DESACTIVATION_PROMOTION = 6;

    use TraitTimeUpdated;


    


    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $enabled = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $price = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $description = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $typeModification = null;

    /**
     * @ORM\ManyToOne(targetEntity=ProductSaleChannel::class, inversedBy="productSaleChannelHistories")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?\App\Entity\ProductSaleChannel $productSaleChannel = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $regularPrice = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $promotionPrice = null;


    public function getFullDescription()
    {
        $description = $this->getTypeModificationLitteral();
        if ($this->description) {
            $description.= ' > '.$this->description;
        }
        return $description;
    }


    public function getTypeModificationLitteral()
    {
        return match ($this->typeModification) {
            self::TYPE_CREATION => 'Creation',
            self::TYPE_ACTIVATION => 'Activation',
            self::TYPE_DESACTIVATION => 'Desactiviation',
            self::TYPE_MODIFICATION_REGULAR_PRICE => 'Regular price modification',
            self::TYPE_MODIFICATION_SALE_PRICE => 'Sale price modification',
            self::TYPE_ACTIVATION_PROMOTION => 'Promotion activation',
            self::TYPE_DESACTIVATION_PROMOTION => 'Promotion desactivation',
            default => 'Other',
        };
    }
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
