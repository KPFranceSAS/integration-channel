<?php

namespace App\Entity;

use App\Entity\Product;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class ProductStockDaily
{
    public static function buildOneFromProduct(Product $product): ProductStockDaily
    {
        $stockDaily = new ProductStockDaily();
        $stockDaily->setFbaSellableStock($product->getFbaSellableStock());
        $stockDaily->setFbaUnsellableStock($product->getFbaUnsellableStock());
        $stockDaily->setFbaInboundStock($product->getFbaInboundStock());
        $stockDaily->setFbaOutboundStock($product->getFbaOutboundStock());
        $stockDaily->setFbaReservedStock($product->getFbaReservedStock());
        $stockDaily->setFbaInboundShippedStock($product->getFbaInboundShippedStock());
        $stockDaily->setFbaInboundWorkingStock($product->getFbaInboundWorkingStock());
        $stockDaily->setFbaInboundReceivingStock($product->getFbaInboundReceivingStock());
        $stockDaily->setFbaResearchingStock($product->getFbaResearchingStock());
        $stockDaily->setFbaTotalStock($product->getFbaTotalStock());

        $stockDaily->setFbaEuSellableStock($product->getFbaEuSellableStock());
        $stockDaily->setFbaEuUnsellableStock($product->getFbaEuUnsellableStock());
        $stockDaily->setFbaEuInboundStock($product->getFbaEuInboundStock());
        $stockDaily->setFbaEuOutboundStock($product->getFbaEuOutboundStock());
        $stockDaily->setFbaEuReservedStock($product->getFbaEuReservedStock());
        $stockDaily->setFbaEuInboundShippedStock($product->getFbaEuInboundShippedStock());
        $stockDaily->setFbaEuInboundWorkingStock($product->getFbaEuInboundWorkingStock());
        $stockDaily->setFbaEuInboundReceivingStock($product->getFbaEuInboundReceivingStock());
        $stockDaily->setFbaEuResearchingStock($product->getFbaEuResearchingStock());
        $stockDaily->setFbaEuTotalStock($product->getFbaEuTotalStock());

        $stockDaily->setFbaUkSellableStock($product->getFbaUkSellableStock());
        $stockDaily->setFbaUkUnsellableStock($product->getFbaUkUnsellableStock());
        $stockDaily->setFbaUkInboundStock($product->getFbaUkInboundStock());
        $stockDaily->setFbaUkOutboundStock($product->getFbaUkOutboundStock());
        $stockDaily->setFbaUkReservedStock($product->getFbaUkReservedStock());
        $stockDaily->setFbaUkInboundShippedStock($product->getFbaUkInboundShippedStock());
        $stockDaily->setFbaUkInboundWorkingStock($product->getFbaUkInboundWorkingStock());
        $stockDaily->setFbaUkInboundReceivingStock($product->getFbaUkInboundReceivingStock());
        $stockDaily->setFbaUkResearchingStock($product->getFbaUkResearchingStock());
        $stockDaily->setFbaUkTotalStock($product->getFbaUkTotalStock());


        
        $stockDaily->setLaRocaBusinessCentralStock($product->getLaRocaBusinessCentralStock());
        $stockDaily->setLaRocaPurchaseBusinessCentralStock($product->getLaRocaPurchaseBusinessCentralStock());
        $stockDaily->setBusinessCentralStock($product->getBusinessCentralStock());
        $stockDaily->setBusinessCentralTotalStock($product->getBusinessCentralTotalStock());

        $stockDaily->setProduct($product);
        return $stockDaily;
    }


    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     */
    private $id;


    /**
     * @ORM\Column(type="datetime")
     */
    private $stockDate;

   


    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    
    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaSellableStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaUnsellableStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaInboundStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaOutboundStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaReservedStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaInboundShippedStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaInboundWorkingStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaInboundReceivingStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaResearchingStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaTotalStock = 0;


    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaEuSellableStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaEuUnsellableStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaEuInboundStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaEuOutboundStock = 0;



    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaEuReservedStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaEuInboundShippedStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaEuInboundWorkingStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaEuInboundReceivingStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaEuResearchingStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaEuTotalStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaUkSellableStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaUkUnsellableStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaUkInboundStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaUkOutboundStock = 0;



    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaUkReservedStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaUkInboundShippedStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaUkInboundWorkingStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaUkInboundReceivingStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaUkResearchingStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $fbaUkTotalStock = 0;


    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $laRocaBusinessCentralStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $laRocaPurchaseBusinessCentralStock = 0;


    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $businessCentralTotalStock = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"export_product"})
     */
    private $businessCentralStock = 0;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $product;


    /**
    *  @Groups({"export_order"})
    */
    public function getProductId()
    {
        return $this->product ? $this->product->getId() :  null;
    }


    /**
    *  @Groups({"export_order"})
    */
    public function getStockDateFormatYmd()
    {
        return $this->stockDate->format('Y-m-d');
    }


    /**
    *  @Groups({"export_order"})
    */
    public function getStockDateFormatCalendar()
    {
        return $this->stockDate->format('j/n/Y');
    }


    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new DateTimeImmutable();
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStockDate(): ?\DateTimeInterface
    {
        return $this->stockDate;
    }

    public function setStockDate(\DateTimeInterface $stockDate): self
    {
        $this->stockDate = $stockDate;

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

    public function getFbaTotalStock(): ?int
    {
        return $this->fbaTotalStock;
    }

    public function setFbaTotalStock(?int $fbaTotalStock): self
    {
        $this->fbaTotalStock = $fbaTotalStock;

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

    public function getLaRocaBusinessCentralStock(): ?int
    {
        return $this->laRocaBusinessCentralStock;
    }

    public function setLaRocaBusinessCentralStock(?int $laRocaBusinessCentralStock): self
    {
        $this->laRocaBusinessCentralStock = $laRocaBusinessCentralStock;

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

    public function getBusinessCentralTotalStock(): ?int
    {
        return $this->businessCentralTotalStock;
    }

    public function setBusinessCentralTotalStock(?int $businessCentralTotalStock): self
    {
        $this->businessCentralTotalStock = $businessCentralTotalStock;

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
}
