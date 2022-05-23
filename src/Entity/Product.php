<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class Product
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"export_product"})
     */
    private $sku;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_product"})
     */
    private $asin;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_product"})
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $comments;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"export_product"})
     */
    private $active = true;

    /**
     * @ORM\ManyToOne(targetEntity=Brand::class, inversedBy="products")
     */
    private $brand;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_product"})
     */
    private $fnsku;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="products")
     */
    private $category;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fbaSellableStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fbaUnsellableStock= 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fbaInboundStock= 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fbaOutboundStock= 0;



    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fbaReservedStock= 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fbaInboundShippedStock= 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fbaInboundWorkingStock= 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fbaInboundReceivingStock= 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fbaResearchingStock= 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fbaTotalStock= 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $laRocaBusinessCentralStock= 0;



    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $businessCentralTotalStock= 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $businessCentralStock= 0;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $ratioStock= 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $soldStockNotIntegrated= 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $returnStockNotIntegrated= 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $differenceStock= 0;



    /**
     * @Groups({"export_product"})
     */
    public function getProductId()
    {
        return $this->getId();
    }

    /**
     *
     * @Groups({"export_product"})
     */
    public function getBrandName()
    {
        return $this->brand ? $this->brand->getName() : 'NO BRAND';
    }


    /**
     *
     * @Groups({"export_product"})
     */
    public function getCategoryName()
    {
        return $this->category ? $this->category->getName() : 'NO CATEGORY';
    }



    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->calculateRatio();
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->calculateRatio();
    }

    private function calculateRatio()
    {
        $this->fbaTotalStock = $this->fbaSellableStock + $this->fbaUnsellableStock + $this->fbaResearchingStock + $this->fbaReservedStock;
        $this->businessCentralTotalStock = $this->businessCentralStock - $this->soldStockNotIntegrated + $this->returnStockNotIntegrated;
        $this->fbaInboundStock = $this->fbaInboundReceivingStock + $this->fbaInboundShippedStock + $this->fbaInboundWorkingStock;
        
        $this->differenceStock = abs($this->fbaTotalStock - $this->businessCentralTotalStock);

        if ($this->differenceStock == 0) {
            $this->ratioStock = 0;
        } else {
            if ($this->businessCentralTotalStock!=0) {
                $this->ratioStock = abs(round($this->differenceStock/($this->businessCentralTotalStock), 4));
            } elseif ($this->fbaTotalStock!=0) {
                $this->ratioStock = abs(round($this->differenceStock/($this->fbaTotalStock), 4));
            } else {
                $this->ratioStock = 0;
            }
        }
    }

    public function addFbaSellableStock(int $stock)
    {
        $this->fbaSellableStock+=$stock;
    }


    public function addFbaReservedStock(int $stock)
    {
        $this->fbaReservedStock+=$stock;
    }

    public function addFbaRearchingStock(int $stock)
    {
        $this->fbaResearchingStock+=$stock;
    }


    public function addFbaUnsellableStock(int $stock)
    {
        $this->fbaUnsellableStock+=$stock;
    }

    public function addFbaInboundReceivingStock(int $stock)
    {
        $this->fbaInboundReceivingStock+=$stock;
    }

    public function addFbaInboundWorkingStock(int $stock)
    {
        $this->fbaInboundWorkingStock+=$stock;
    }

    public function addFbaInboundShippedStock(int $stock)
    {
        $this->fbaInboundShippedStock+=$stock;
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

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

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
}
