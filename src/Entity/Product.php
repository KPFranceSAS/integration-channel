<?php

namespace App\Entity;

use App\Entity\ProductSaleChannel;
use App\Entity\ProductStockDaily;
use App\Helper\Traits\TraitTimeUpdated;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\Loggable]
class Product implements \Stringable
{
    use TraitTimeUpdated;

    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    #[Groups(['export_product'])]
    private ?string $sku = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    #[Groups(['export_product'])]
    private ?string $asin = null;


    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    #[Groups(['export_product'])]
    #[Assert\NotNull]
    private ?string $description = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    private ?string $comments = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    #[Groups(['export_product'])]
    private ?bool $active = true;

    #[ORM\ManyToOne(targetEntity: Brand::class, inversedBy: 'products')]
    #[Assert\NotNull]
    private ?\App\Entity\Brand $brand = null;


    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    private ?bool $enabledFbm = false;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    #[Groups(['export_product'])]
    private ?string $fnsku = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    private ?\App\Entity\Category $category = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaSellableStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaUnsellableStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaInboundStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaOutboundStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaReservedStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaInboundShippedStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaInboundWorkingStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaInboundReceivingStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaResearchingStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaTotalStock = 0;


    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaEuSellableStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaEuUnsellableStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaEuInboundStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaEuOutboundStock = 0;



    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaEuReservedStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaEuInboundShippedStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaEuInboundWorkingStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaEuInboundReceivingStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaEuResearchingStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaEuTotalStock = 0;




    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaUkSellableStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaUkUnsellableStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaUkInboundStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaUkOutboundStock = 0;



    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaUkReservedStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaUkInboundShippedStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaUkInboundWorkingStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaUkInboundReceivingStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaUkResearchingStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $fbaUkTotalStock = 0;


    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $laRocaBusinessCentralStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $laRocaPurchaseBusinessCentralStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    private ?int $uk3plBusinessCentralStock= 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    private ?int $uk3plPurchaseBusinessCentralStock= 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $businessCentralTotalStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $businessCentralStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    #[Groups(['export_product'])]
    private ?float $ratioStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $soldStockNotIntegrated = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $returnStockNotIntegrated = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Groups(['export_product'])]
    private ?int $differenceStock = 0;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Assert\GreaterThan(value: 0)]
    #[Groups(['export_product'])]
    private ?int $minQtyFbaEu = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Assert\GreaterThan(value: 0)]
    #[Groups(['export_product'])]
    private ?int $minQtyFbaUk = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    #[Groups(['export_product'])]
    private ?float $unitCost = null;

    /**
     *
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\ProductSaleChannel>
     */
    #[Assert\Valid]
    #[ORM\OneToMany(targetEntity: ProductSaleChannel::class, mappedBy: 'product', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private \Doctrine\Common\Collections\Collection $productSaleChannels;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\SalePrice>
     */
    #[ORM\OneToMany(targetEntity: SalePrice::class, mappedBy: 'product', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private \Doctrine\Common\Collections\Collection $salePrices;

    
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    #[Assert\GreaterThan(value: 0)]
    private ?float $eurPrice = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    #[Assert\GreaterThan(value: 0)]
    private ?float $gbpPrice = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\ProductCorrelation>
     */
    #[ORM\OneToMany(targetEntity: ProductCorrelation::class, mappedBy: 'product', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private \Doctrine\Common\Collections\Collection $productCorrelations;

    /**
    * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\ProductStockDaily>
    */
    #[ORM\OneToMany(targetEntity: ProductStockDaily::class, mappedBy: 'product', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private \Doctrine\Common\Collections\Collection $productStockDailys;


    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    private ?string $ean = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    private ?bool $dangerousGood = false;

    #[ORM\ManyToOne(targetEntity: LogisticClass::class, inversedBy: 'products')]
    private ?\App\Entity\LogisticClass $logisticClass = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    private ?bool $freeShipping = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $productType = null;

    #[ORM\Column(nullable: true)]
    private ?float $msrpEur = null;

    #[ORM\Column(nullable: true)]
    private ?float $msrpGbp = null;

    #[ORM\Column(nullable: true)]
    private ?float $ecotax = null;

    #[ORM\Column(nullable: true)]
    private ?float $canonDigital = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gsprName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gsprAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gsprEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gsprCountry = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gsprCity = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gsprPostalCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gsprWebsite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gsprPhone = null;



    public function __construct()
    {
        $this->productSaleChannels = new ArrayCollection();
        $this->salePrices = new ArrayCollection();
        $this->productCorrelations = new ArrayCollection();
        $this->productStockDailys = new ArrayCollection();
    }


    public function __toString(): string
    {
        return $this->sku.' '.$this->description;
    }



    public function getRegularPriceOnMarketplace($marketplace) : ?float
    {
        $productMarketplace = $this->getProductSaleChannelByCode($marketplace);
        return $productMarketplace->getPriceChannel();
    }


    public function isOnsaleOnMArketplace($marketplace): bool
    {
        $productMarketplace = $this->getProductSaleChannelByCode($marketplace);
        return $productMarketplace->getEnabled();
    }



    public function getSalePriceForNowOnMarketplace($marketplace) : ?float
    {
        $now = new DateTime('now');
        return $this->getSalePriceOnMarketplace($now, $marketplace);
    }


    public function getSalePriceOnMarketplace(DateTime $date, $marketplace) : ?float
    {
        $productMarketplace = $this->getProductSaleChannelByCode($marketplace);
        return $productMarketplace->getSalePrice($date);
    }


    




    public function getProductSaleChannelByCode($code): ?ProductSaleChannel
    {
        foreach ($this->productSaleChannels as $productSaleChannel) {
            if ($productSaleChannel->getSaleChannel()->getCode() == $code) {
                return $productSaleChannel;
            }
        }
        return null;
    }


    #[Groups(['export_product'])]
    public function getProductId(): int
    {
        return $this->getId();
    }

    
    #[Groups(['export_product'])]
    public function getBrandName(): string
    {
        return $this->brand ? $this->brand->getName() : 'NO BRAND';
    }


    
    #[Groups(['export_product'])]
    public function getCategoryName(): string
    {
        return $this->category ? $this->category->getName() : 'NO CATEGORY';
    }




    
    #[Groups(['export_product'])]
    public function getNeedTobeAlertEu(): bool
    {
        return $this->needTobeAlertEu();
    }

    
    #[Groups(['export_product'])]
    public function getNeedTobeAlertUk(): bool
    {
        return $this->needTobeAlertUk();
    }



    public function needTobeAlertEu(): bool
    {
        return $this->needTobeAlert('Eu', 'laRoca');
    }

    public function needTobeAlertUk(): bool
    {
        return $this->needTobeAlert('Uk', 'uk3pl');
    }


    public function needTobeAlert($zone, $fieldEntrepot): bool
    {
        if ($this->{'minQtyFba' . $zone} && $this->{$fieldEntrepot.'BusinessCentralStock'} > 0) {
            $stock = $this->{'fba' . $zone . 'SellableStock'} + $this->{'fba' . $zone . 'InboundStock'};
            return ($stock <= $this->{'minQtyFba' . $zone});
        }
        return false;
    }


    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->calculateRatio();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->calculateRatio();
    }

    private function calculateRatio(): void
    {
        $this->fbaTotalStock = $this->fbaSellableStock + $this->fbaUnsellableStock + $this->fbaResearchingStock + $this->fbaReservedStock;
        $this->fbaEuTotalStock = $this->fbaEuSellableStock + $this->fbaEuUnsellableStock + $this->fbaEuResearchingStock + $this->fbaEuReservedStock;
        $this->fbaUkTotalStock = $this->fbaUkSellableStock + $this->fbaUkUnsellableStock + $this->fbaUkResearchingStock + $this->fbaUkReservedStock;

        $this->businessCentralTotalStock = $this->businessCentralStock - $this->soldStockNotIntegrated + $this->returnStockNotIntegrated;
        $this->fbaInboundStock = $this->fbaInboundReceivingStock + $this->fbaInboundShippedStock + $this->fbaInboundWorkingStock;
        $this->fbaEuInboundStock = $this->fbaEuInboundReceivingStock + $this->fbaEuInboundShippedStock + $this->fbaEuInboundWorkingStock;
        $this->fbaUkInboundStock = $this->fbaUkInboundReceivingStock + $this->fbaUkInboundShippedStock + $this->fbaUkInboundWorkingStock;

        $this->differenceStock = abs($this->fbaTotalStock - $this->businessCentralTotalStock);

        if ($this->differenceStock == 0) {
            $this->ratioStock = 0;
        } else {
            if ($this->businessCentralTotalStock != 0) {
                $this->ratioStock = abs(round($this->differenceStock / ($this->businessCentralTotalStock), 4));
            } elseif ($this->fbaTotalStock != 0) {
                $this->ratioStock = abs(round($this->differenceStock / ($this->fbaTotalStock), 4));
            } else {
                $this->ratioStock = 0;
            }
        }
    }

    public function addFbaSellableStock(int $stock, string $marketplace): void
    {
        $this->fbaSellableStock += $stock;
        $this->{'fba' . ucfirst($marketplace) . 'SellableStock'} += $stock;
    }


    public function addFbaReservedStock(int $stock, string $marketplace): void
    {
        $this->fbaReservedStock += $stock;
        $this->{'fba' . ucfirst($marketplace) . 'ReservedStock'} += $stock;
    }

    public function addFbaRearchingStock(int $stock, string $marketplace): void
    {
        $this->fbaResearchingStock += $stock;
        $this->{'fba' . ucfirst($marketplace) . 'ResearchingStock'} += $stock;
    }


    public function addFbaUnsellableStock(int $stock, string $marketplace): void
    {
        $this->fbaUnsellableStock += $stock;
        $this->{'fba' . ucfirst($marketplace) . 'UnsellableStock'} += $stock;
    }

    public function addFbaInboundReceivingStock(int $stock, string $marketplace): void
    {
        $this->fbaInboundReceivingStock += $stock;
        $this->{'fba' . ucfirst($marketplace) . 'InboundReceivingStock'} += $stock;
    }

    public function addFbaInboundWorkingStock(int $stock, string $marketplace): void
    {
        $this->fbaInboundWorkingStock += $stock;
        $this->{'fba' . ucfirst($marketplace) . 'InboundWorkingStock'} += $stock;
    }

    public function addFbaInboundShippedStock(int $stock, string $marketplace): void
    {
        $this->fbaInboundShippedStock += $stock;
        $this->{'fba' . ucfirst($marketplace) . 'InboundShippedStock'} += $stock;
    }


    public function getId(): ?int
    {
        return $this->id;
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

    public function getAsin(): ?string
    {
        return $this->asin;
    }

    public function setAsin(?string $asin): self
    {
        $this->asin = $asin;

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

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): self
    {
        $this->comments = $comments;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(?Brand $brand): self
    {
        $this->brand = $brand;

        return $this;
    }



    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getFnsku(): ?string
    {
        return $this->fnsku;
    }

    public function setFnsku(?string $fnsku): self
    {
        $this->fnsku = $fnsku;

        return $this;
    }

    public function getFbaSellableStock(): ?int
    {
        return $this->fbaSellableStock;
    }

    public function setFbaSellableStock(?int $fbaSellableStock): self
    {
        $this->fbaSellableStock = $fbaSellableStock;

        return $this;
    }

    public function getFbaUnsellableStock(): ?int
    {
        return $this->fbaUnsellableStock;
    }

    public function setFbaUnsellableStock(?int $fbaUnsellableStock): self
    {
        $this->fbaUnsellableStock = $fbaUnsellableStock;

        return $this;
    }

    public function getFbaInboundStock(): ?int
    {
        return $this->fbaInboundStock;
    }

    public function setFbaInboundStock(?int $fbaInboundStock): self
    {
        $this->fbaInboundStock = $fbaInboundStock;

        return $this;
    }

    public function getFbaOutboundStock(): ?int
    {
        return $this->fbaOutboundStock;
    }

    public function setFbaOutboundStock(?int $fbaOutboundStock): self
    {
        $this->fbaOutboundStock = $fbaOutboundStock;

        return $this;
    }

    public function getBusinessCentralStock(): ?int
    {
        return $this->businessCentralStock;
    }

    public function setBusinessCentralStock(?int $businessCentralStock): self
    {
        $this->businessCentralStock = $businessCentralStock;

        return $this;
    }

    public function getRatioStock(): ?float
    {
        return $this->ratioStock;
    }

    public function setRatioStock(?float $ratioStock): self
    {
        $this->ratioStock = $ratioStock;

        return $this;
    }

    public function getSoldStockNotIntegrated(): ?int
    {
        return $this->soldStockNotIntegrated;
    }

    public function setSoldStockNotIntegrated(?int $soldStockNotIntegrated): self
    {
        $this->soldStockNotIntegrated = $soldStockNotIntegrated;
        return $this;
    }

    public function getReturnStockNotIntegrated(): ?int
    {
        return $this->returnStockNotIntegrated;
    }

    public function setReturnStockNotIntegrated(?int $returnStockNotIntegrated): self
    {
        $this->returnStockNotIntegrated = $returnStockNotIntegrated;

        return $this;
    }

    public function getDifferenceStock(): ?int
    {
        return $this->differenceStock;
    }

    public function setDifferenceStock(?int $differenceStock): self
    {
        $this->differenceStock = $differenceStock;

        return $this;
    }

    public function getFbaReservedStock(): ?int
    {
        return $this->fbaReservedStock;
    }

    public function setFbaReservedStock(?int $fbaReservedStock): self
    {
        $this->fbaReservedStock = $fbaReservedStock;

        return $this;
    }

    public function getFbaInboundShippedStock(): ?int
    {
        return $this->fbaInboundShippedStock;
    }

    public function setFbaInboundShippedStock(?int $fbaInboundShippedStock): self
    {
        $this->fbaInboundShippedStock = $fbaInboundShippedStock;

        return $this;
    }

    public function getFbaInboundWorkingStock(): ?int
    {
        return $this->fbaInboundWorkingStock;
    }

    public function setFbaInboundWorkingStock(?int $fbaInboundWorkingStock): self
    {
        $this->fbaInboundWorkingStock = $fbaInboundWorkingStock;

        return $this;
    }

    public function getFbaInboundReceivingStock(): ?int
    {
        return $this->fbaInboundReceivingStock;
    }

    public function setFbaInboundReceivingStock(?int $fbaInboundReceivingStock): self
    {
        $this->fbaInboundReceivingStock = $fbaInboundReceivingStock;

        return $this;
    }

    public function getFbaResearchingStock(): ?int
    {
        return $this->fbaResearchingStock;
    }

    public function setFbaResearchingStock(?int $fbaResearchingStock): self
    {
        $this->fbaResearchingStock = $fbaResearchingStock;

        return $this;
    }

    public function getLaRocaBusinessCentralStock(): ?int
    {
        return $this->laRocaBusinessCentralStock;
    }

    public function setLaRocaBusinessCentralStock(?int $laRocaBusinessCentralStock): self
    {
        $this->laRocaBusinessCentralStock = $laRocaBusinessCentralStock;

        return $this;
    }

    public function getFbaTotalStock(): ?int
    {
        return $this->fbaTotalStock;
    }

    public function setFbaTotalStock(?int $fbaTotalStock): self
    {
        $this->fbaTotalStock = $fbaTotalStock;

        return $this;
    }

    public function getBusinessCentralTotalStock(): ?int
    {
        return $this->businessCentralTotalStock;
    }

    public function setBusinessCentralTotalStock(int $businessCentralTotalStock): self
    {
        $this->businessCentralTotalStock = $businessCentralTotalStock;

        return $this;
    }

    public function getMinQtyFbaEu(): ?int
    {
        return $this->minQtyFbaEu;
    }

    public function setMinQtyFbaEu(?int $minQtyFbaEu): self
    {
        $this->minQtyFbaEu = $minQtyFbaEu;

        return $this;
    }

    public function getMinQtyFbaUk(): ?int
    {
        return $this->minQtyFbaUk;
    }

    public function setMinQtyFbaUk(?int $minQtyFbaUk): self
    {
        $this->minQtyFbaUk = $minQtyFbaUk;

        return $this;
    }

    public function getFbaEuSellableStock(): ?int
    {
        return $this->fbaEuSellableStock;
    }

    public function setFbaEuSellableStock(?int $fbaEuSellableStock): self
    {
        $this->fbaEuSellableStock = $fbaEuSellableStock;

        return $this;
    }

    public function getFbaEuUnsellableStock(): ?int
    {
        return $this->fbaEuUnsellableStock;
    }

    public function setFbaEuUnsellableStock(?int $fbaEuUnsellableStock): self
    {
        $this->fbaEuUnsellableStock = $fbaEuUnsellableStock;

        return $this;
    }

    public function getFbaEuInboundStock(): ?int
    {
        return $this->fbaEuInboundStock;
    }

    public function setFbaEuInboundStock(?int $fbaEuInboundStock): self
    {
        $this->fbaEuInboundStock = $fbaEuInboundStock;

        return $this;
    }

    public function getFbaEuOutboundStock(): ?int
    {
        return $this->fbaEuOutboundStock;
    }

    public function setFbaEuOutboundStock(?int $fbaEuOutboundStock): self
    {
        $this->fbaEuOutboundStock = $fbaEuOutboundStock;

        return $this;
    }

    public function getFbaEuReservedStock(): ?int
    {
        return $this->fbaEuReservedStock;
    }

    public function setFbaEuReservedStock(?int $fbaEuReservedStock): self
    {
        $this->fbaEuReservedStock = $fbaEuReservedStock;

        return $this;
    }

    public function getFbaEuInboundShippedStock(): ?int
    {
        return $this->fbaEuInboundShippedStock;
    }

    public function setFbaEuInboundShippedStock(?int $fbaEuInboundShippedStock): self
    {
        $this->fbaEuInboundShippedStock = $fbaEuInboundShippedStock;

        return $this;
    }

    public function getFbaEuInboundWorkingStock(): ?int
    {
        return $this->fbaEuInboundWorkingStock;
    }

    public function setFbaEuInboundWorkingStock(?int $fbaEuInboundWorkingStock): self
    {
        $this->fbaEuInboundWorkingStock = $fbaEuInboundWorkingStock;

        return $this;
    }

    public function getFbaEuInboundReceivingStock(): ?int
    {
        return $this->fbaEuInboundReceivingStock;
    }

    public function setFbaEuInboundReceivingStock(?int $fbaEuInboundReceivingStock): self
    {
        $this->fbaEuInboundReceivingStock = $fbaEuInboundReceivingStock;

        return $this;
    }

    public function getFbaEuResearchingStock(): ?int
    {
        return $this->fbaEuResearchingStock;
    }

    public function setFbaEuResearchingStock(?int $fbaEuResearchingStock): self
    {
        $this->fbaEuResearchingStock = $fbaEuResearchingStock;

        return $this;
    }

    public function getFbaEuTotalStock(): ?int
    {
        return $this->fbaEuTotalStock;
    }

    public function setFbaEuTotalStock(?int $fbaEuTotalStock): self
    {
        $this->fbaEuTotalStock = $fbaEuTotalStock;

        return $this;
    }

    public function getFbaUkSellableStock(): ?int
    {
        return $this->fbaUkSellableStock;
    }

    public function setFbaUkSellableStock(?int $fbaUkSellableStock): self
    {
        $this->fbaUkSellableStock = $fbaUkSellableStock;

        return $this;
    }

    public function getFbaUkUnsellableStock(): ?int
    {
        return $this->fbaUkUnsellableStock;
    }

    public function setFbaUkUnsellableStock(?int $fbaUkUnsellableStock): self
    {
        $this->fbaUkUnsellableStock = $fbaUkUnsellableStock;

        return $this;
    }

    public function getFbaUkInboundStock(): ?int
    {
        return $this->fbaUkInboundStock;
    }

    public function setFbaUkInboundStock(?int $fbaUkInboundStock): self
    {
        $this->fbaUkInboundStock = $fbaUkInboundStock;

        return $this;
    }

    public function getFbaUkOutboundStock(): ?int
    {
        return $this->fbaUkOutboundStock;
    }

    public function setFbaUkOutboundStock(?int $fbaUkOutboundStock): self
    {
        $this->fbaUkOutboundStock = $fbaUkOutboundStock;

        return $this;
    }

    public function getFbaUkReservedStock(): ?int
    {
        return $this->fbaUkReservedStock;
    }

    public function setFbaUkReservedStock(?int $fbaUkReservedStock): self
    {
        $this->fbaUkReservedStock = $fbaUkReservedStock;

        return $this;
    }

    public function getFbaUkInboundShippedStock(): ?int
    {
        return $this->fbaUkInboundShippedStock;
    }

    public function setFbaUkInboundShippedStock(?int $fbaUkInboundShippedStock): self
    {
        $this->fbaUkInboundShippedStock = $fbaUkInboundShippedStock;

        return $this;
    }

    public function getFbaUkInboundWorkingStock(): ?int
    {
        return $this->fbaUkInboundWorkingStock;
    }

    public function setFbaUkInboundWorkingStock(?int $fbaUkInboundWorkingStock): self
    {
        $this->fbaUkInboundWorkingStock = $fbaUkInboundWorkingStock;

        return $this;
    }

    public function getFbaUkInboundReceivingStock(): ?int
    {
        return $this->fbaUkInboundReceivingStock;
    }

    public function setFbaUkInboundReceivingStock(?int $fbaUkInboundReceivingStock): self
    {
        $this->fbaUkInboundReceivingStock = $fbaUkInboundReceivingStock;

        return $this;
    }

    public function getFbaUkResearchingStock(): ?int
    {
        return $this->fbaUkResearchingStock;
    }

    public function setFbaUkResearchingStock(?int $fbaUkResearchingStock): self
    {
        $this->fbaUkResearchingStock = $fbaUkResearchingStock;

        return $this;
    }

    public function getFbaUkTotalStock(): ?int
    {
        return $this->fbaUkTotalStock;
    }

    public function setFbaUkTotalStock(?int $fbaUkTotalStock): self
    {
        $this->fbaUkTotalStock = $fbaUkTotalStock;

        return $this;
    }

    public function getLaRocaPurchaseBusinessCentralStock(): ?int
    {
        return $this->laRocaPurchaseBusinessCentralStock;
    }

    public function setLaRocaPurchaseBusinessCentralStock(?int $laRocaPurchaseBusinessCentralStock): self
    {
        $this->laRocaPurchaseBusinessCentralStock = $laRocaPurchaseBusinessCentralStock;

        return $this;
    }

    public function getUnitCost(): ?float
    {
        return $this->unitCost;
    }

    public function setUnitCost(?float $unitCost): self
    {
        $this->unitCost = $unitCost;

        return $this;
    }

    /**
     * @return Collection|ProductSaleChannel[]
     */
    public function getProductSaleChannels(): Collection
    {
        return $this->productSaleChannels;
    }

    public function addProductSaleChannel(ProductSaleChannel $productSaleChannel): self
    {
        if (!$this->productSaleChannels->contains($productSaleChannel)) {
            $this->productSaleChannels[] = $productSaleChannel;
            $productSaleChannel->setProduct($this);
        }

        return $this;
    }

    public function removeProductSaleChannel(ProductSaleChannel $productSaleChannel): self
    {
        if ($this->productSaleChannels->removeElement($productSaleChannel)) {
            // set the owning side to null (unless already changed)
            if ($productSaleChannel->getProduct() === $this) {
                $productSaleChannel->setProduct(null);
            }
        }

        return $this;
    }




    /**
     * @return Collection|ProductStockDaily[]
     */
    public function getProductStockDailys(): Collection
    {
        return $this->productStockDailys;
    }

    public function addProductStockDaily(ProductStockDaily $productStockDaily): self
    {
        if (!$this->productStockDailys->contains($productStockDaily)) {
            $this->productStockDailys[] = $productStockDaily;
            $productStockDaily->setProduct($this);
        }

        return $this;
    }

    public function removeProductStockDaily(ProductStockDaily $productStockDaily): self
    {
        if ($this->productStockDailys->removeElement($productStockDaily)) {
            // set the owning side to null (unless already changed)
            if ($productStockDaily->getProduct() === $this) {
                $productStockDaily->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|SalePrice[]
     */
    public function getSalePrices(): Collection
    {
        return $this->salePrices;
    }

    public function addSalePrice(SalePrice $salePrice): self
    {
        if (!$this->salePrices->contains($salePrice)) {
            $this->salePrices[] = $salePrice;
            $salePrice->setProduct($this);
        }

        return $this;
    }

    public function removeSalePrice(SalePrice $salePrice): self
    {
        if ($this->salePrices->removeElement($salePrice)) {
            // set the owning side to null (unless already changed)
            if ($salePrice->getProduct() === $this) {
                $salePrice->setProduct(null);
            }
        }

        return $this;
    }

    public function getEurPrice(): ?float
    {
        return $this->eurPrice;
    }

    public function setEurPrice(?float $eurPrice): self
    {
        $this->eurPrice = $eurPrice;

        return $this;
    }

    public function getGbpPrice(): ?float
    {
        return $this->gbpPrice;
    }

    public function setGbpPrice(?float $gbpPrice): self
    {
        $this->gbpPrice = $gbpPrice;

        return $this;
    }

    /**
     * @return Collection<int, ProductCorrelation>
     */
    public function getProductCorrelations(): Collection
    {
        return $this->productCorrelations;
    }

    public function addProductCorrelation(ProductCorrelation $productCorrelation): self
    {
        if (!$this->productCorrelations->contains($productCorrelation)) {
            $this->productCorrelations[] = $productCorrelation;
            $productCorrelation->setProduct($this);
        }

        return $this;
    }

    public function removeProductCorrelation(ProductCorrelation $productCorrelation): self
    {
        if ($this->productCorrelations->removeElement($productCorrelation)) {
            // set the owning side to null (unless already changed)
            if ($productCorrelation->getProduct() === $this) {
                $productCorrelation->setProduct(null);
            }
        }

        return $this;
    }

    public function getUk3plBusinessCentralStock(): ?int
    {
        return $this->uk3plBusinessCentralStock;
    }

    public function setUk3plBusinessCentralStock(?int $uk3plBusinessCentralStock): self
    {
        $this->uk3plBusinessCentralStock = $uk3plBusinessCentralStock;

        return $this;
    }

    public function getUk3plPurchaseBusinessCentralStock(): ?int
    {
        return $this->uk3plPurchaseBusinessCentralStock;
    }

    public function setUk3plPurchaseBusinessCentralStock(?int $uk3plPurchaseBusinessCentralStock): self
    {
        $this->uk3plPurchaseBusinessCentralStock = $uk3plPurchaseBusinessCentralStock;

        return $this;
    }

    public function getEan(): ?string
    {
        return $this->ean;
    }

    public function setEan(?string $ean): self
    {
        $this->ean = $ean;

        return $this;
    }


    public function getEnabledFbm(): ?bool
    {
        return $this->enabledFbm;
    }


    public function isEnabledFbm(): ?bool
    {
        return $this->enabledFbm;
    }

    public function setEnabledFbm(?bool $enabledFbm): static
    {
        $this->enabledFbm = $enabledFbm;

        return $this;
    }

    public function isDangerousGood(): ?bool
    {
        return $this->dangerousGood;
    }


    public function getDangerousGood(): ?bool
    {
        return $this->isDangerousGood();
    }

    public function setDangerousGood(?bool $dangerousGood): self
    {
        $this->dangerousGood = $dangerousGood;

        return $this;
    }

    public function getLogisticClass(): ?LogisticClass
    {
        return $this->logisticClass;
    }

    public function setLogisticClass(?LogisticClass $logisticClass): self
    {
        $this->logisticClass = $logisticClass;

        return $this;
    }

    public function isFreeShipping(): ?bool
    {
        return $this->freeShipping;
    }

    public function getFreeShipping(): ?bool
    {
        return $this->freeShipping;
    }

    public function setFreeShipping(?bool $freeShipping): self
    {
        $this->freeShipping = $freeShipping;

        return $this;
    }

    public function getProductType(): ?string
    {
        return $this->productType;
    }

    public function setProductType(?string $productType): static
    {
        $this->productType = $productType;

        return $this;
    }

    public function getMsrpEur(): ?float
    {
        return $this->msrpEur;
    }

    public function setMsrpEur(?float $msrpEur): static
    {
        $this->msrpEur = $msrpEur;

        return $this;
    }

    public function getMsrpGbp(): ?float
    {
        return $this->msrpGbp;
    }

    public function setMsrpGbp(?float $msrpGbp): static
    {
        $this->msrpGbp = $msrpGbp;

        return $this;
    }

    public function getEcotax(): ?float
    {
        return $this->ecotax;
    }

    public function setEcotax(?float $ecotax): static
    {
        $this->ecotax = $ecotax;

        return $this;
    }

    public function getCanonDigital(): ?float
    {
        return $this->canonDigital;
    }

    public function setCanonDigital(?float $canonDigital): static
    {
        $this->canonDigital = $canonDigital;

        return $this;
    }

    public function getGsprName(): ?string
    {
        return $this->gsprName;
    }

    public function setGsprName(?string $gsprName): static
    {
        $this->gsprName = $gsprName;

        return $this;
    }

    public function getGsprAddress(): ?string
    {
        return $this->gsprAddress;
    }

    public function setGsprAddress(?string $gsprAddress): static
    {
        $this->gsprAddress = $gsprAddress;

        return $this;
    }

    public function getGsprEmail(): ?string
    {
        return $this->gsprEmail;
    }

    public function setGsprEmail(?string $gsprEmail): static
    {
        $this->gsprEmail = $gsprEmail;

        return $this;
    }

    public function getGsprCountry(): ?string
    {
        return $this->gsprCountry;
    }

    public function setGsprCountry(?string $gsprCountry): static
    {
        $this->gsprCountry = $gsprCountry;

        return $this;
    }

    public function getGsprCity(): ?string
    {
        return $this->gsprCity;
    }

    public function setGsprCity(?string $gsprCity): static
    {
        $this->gsprCity = $gsprCity;

        return $this;
    }

    public function getGsprPostalCode(): ?string
    {
        return $this->gsprPostalCode;
    }

    public function setGsprPostalCode(?string $gsprPostalCode): static
    {
        $this->gsprPostalCode = $gsprPostalCode;

        return $this;
    }

    public function getGsprWebsite(): ?string
    {
        return $this->gsprWebsite;
    }

    public function setGsprWebsite(?string $gsprWebsite): static
    {
        $this->gsprWebsite = $gsprWebsite;

        return $this;
    }

    public function getGsprPhone(): ?string
    {
        return $this->gsprPhone;
    }

    public function setGsprPhone(?string $gsprPhone): static
    {
        $this->gsprPhone = $gsprPhone;

        return $this;
    }

    

}
