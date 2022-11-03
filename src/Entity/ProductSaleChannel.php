<?php

namespace App\Entity;

use App\Entity\ProductLogEntry;
use App\Entity\ProductSaleChannelHistory;
use App\Entity\Promotion;
use App\Helper\Traits\TraitTimeUpdated;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity()
 * @Gedmo\Loggable(logEntryClass=ProductLogEntry::class)
 * @ORM\HasLifecycleCallbacks()
 */
class ProductSaleChannel
{
    public const TX_MARGIN = 19;

    use TraitTimeUpdated;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class, inversedBy="productSaleChannels")
     * @ORM\JoinColumn(nullable=false)
     */
    private $product;

    /**
     * @ORM\ManyToOne(targetEntity=SaleChannel::class, inversedBy="productSaleChannels")
     * @ORM\JoinColumn(nullable=false)
     */
    private $saleChannel;


    /**
     * @ORM\Column(type="boolean")
     * @Gedmo\Versioned
     */
    private $enabled=false;

    /**
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity=Promotion::class, mappedBy="productSaleChannel", orphanRemoval=true, cascade={"persist","remove"})
     *
     */
    private $promotions;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(type="float", nullable=true)
     * @Assert\Expression(
     *   expression= "this.getEnabled() == false or (this.getEnabled() === true and value !== null)",
     *   message="You must specify the value if Enabled is activated"
     * )
     *  @Assert\GreaterThanOrEqual(0)
     */
    private $price;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $recommendedPrice;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $estimatedCommission;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $estimatedShipping;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $estimatedCommissionPercent;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $estimatedShippingPercent;

    /**
     * @ORM\OneToMany(targetEntity=ProductSaleChannelHistory::class, mappedBy="productSaleChannel", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $productSaleChannelHistories;


    public function getSalePriceForNow()
    {
        $now = new DateTime('now');
        return $this->getSalePrice($now);
    }


    public function getSalePrice(DateTime $date)
    {
        $promotion = $this->getBestPromotionForDate($date);
        return $promotion ? $promotion->getPromotionPrice() : $this->price;
    }


    public function getSalePriceDescription(DateTime $date)
    {
        $promotion = $this->getBestPromotionForDate($date);
        return $promotion ? 'Promotion :'.$promotion->getComment() : 'Regular price';
    }

    


    public function getBestPromotionForNow(): ?Promotion
    {
        $now = new DateTime('now');
        return $this->getBestPromotionForDate($now);
    }

    
    public function getBestPromotionForDate(DateTime $date): ?Promotion
    {
        $bestPromotion= null;
        foreach ($this->promotions as $promotion) {
            if ($promotion->isApplicable($date)) {
                if (!$bestPromotion) {
                    $bestPromotion=$promotion;
                } else {
                    if ($bestPromotion->getPriority() < $promotion->getPriority()) {
                        $bestPromotion=$promotion;
                    } else {
                        if ($promotion->isBetterPromotionThan($bestPromotion)) {
                            $bestPromotion=$promotion;
                        }
                    }
                }
            }
        }
        
        return $bestPromotion;
    }

   
    public function getSaleChannelName()
    {
        return $this->saleChannel->getName();
    }

    public function __toString()
    {
        return $this->product->getBrand().' '.$this->product->getSku().' > '.$this->getSaleChannelName();
    }



    public function checkAndAddHistory(): bool
    {
        $oldProductSaleChannelHistory = $this->getLastProductSaleChannelHistory();
        if (!$oldProductSaleChannelHistory) {
            $this->createFirstRecord();
            return true;
        } else {
            $newProductSaleChannelHistory = $this->createNewRecord();
            if ($this->shouldBeSavedHistoric($newProductSaleChannelHistory, $oldProductSaleChannelHistory)) {
                $this->addProductSaleChannelHistory($newProductSaleChannelHistory);
                return true;
            }
        }
        return false;
    }

    public function createFirstRecord(): ProductSaleChannelHistory
    {
        $productSaleHistory = new ProductSaleChannelHistory();
        $productSaleHistory->setTypeModification(ProductSaleChannelHistory::TYPE_CREATION);
        $productSaleHistory->setEnabled(false);
        $this->addProductSaleChannelHistory($productSaleHistory);
        return $productSaleHistory;
    }



    public function shouldBeSavedHistoric(ProductSaleChannelHistory $new, ProductSaleChannelHistory $old): bool
    {
        if ($new->isEnabled()!=$old->isEnabled()) {
            $new->setTypeModification($new->isEnabled() ? ProductSaleChannelHistory::TYPE_ACTIVATION : ProductSaleChannelHistory::TYPE_DESACTIVATION);
            return true;
        }
        
        

        if ($new->getPrice()!=$old->getPrice()) {
            if ($new->getRegularPrice()!=$old->getRegularPrice()) {
                $new->setTypeModification(ProductSaleChannelHistory::TYPE_MODIFICATION_REGULAR_PRICE);
                return true;
            }

            if ($new->getPromotionPrice()!=$old->getPromotionPrice()) {
                if (!$new->getPromotionPrice()) {
                    $new->setTypeModification(ProductSaleChannelHistory::TYPE_DESACTIVATION_PROMOTION);
                } elseif (!$old->getPromotionPrice()) {
                    $new->setTypeModification(ProductSaleChannelHistory::TYPE_ACTIVATION_PROMOTION);
                } else {
                    $new->setTypeModification(ProductSaleChannelHistory::TYPE_MODIFICATION_SALE_PRICE);
                }
               
                return true;
            }
            return true;
        }
        return false;
    }






    public function createNewRecord(): ProductSaleChannelHistory
    {
        $productSaleHistory = new ProductSaleChannelHistory();
        $productSaleHistory->setEnabled($this->enabled);
        if ($this->enabled) {
            $productSaleHistory->setRegularPrice($this->price);
            $promotion = $this->getBestPromotionForNow();
            if ($promotion) {
                $productSaleHistory->setDescription(strlen($promotion->getComment())>0 ? $promotion->getComment() : substr($promotion->getPromotionDescriptionFrequency().' '.$promotion->getPromotionDescriptionType(), 0, 255));
                $productSaleHistory->setPrice($promotion->getPromotionPrice());
                $productSaleHistory->setPromotionPrice($promotion->getPromotionPrice());
            } else {
                $productSaleHistory->setPrice($this->price);
            }
        }
        return $productSaleHistory;
    }


    public function getLastProductSaleChannelHistory(): ?ProductSaleChannelHistory
    {
        $oldest =null;
        if (count($this->productSaleChannelHistories)> 0) {
            foreach ($this->productSaleChannelHistories as $productHistory) {
                if (!$oldest) {
                    $oldest = $productHistory;
                } else {
                    if ($oldest->getCreatedAt() < $productHistory->getCreatedAt()) {
                        $oldest = $productHistory;
                    }
                }
            }
        }

        return $oldest;
    }


    
    public function __construct()
    {
        $this->promotions = new ArrayCollection();
        $this->productSaleChannelHistories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSaleChannel(): ?SaleChannel
    {
        return $this->saleChannel;
    }

    public function setSaleChannel(?SaleChannel $saleChannel): self
    {
        $this->saleChannel = $saleChannel;

        return $this;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

   
    /**
     * @return Collection|Promotion[]
     */
    public function getPromotions(): Collection
    {
        return $this->promotions;
    }

    public function addPromotion(Promotion $promotion): self
    {
        if (!$this->promotions->contains($promotion)) {
            $this->promotions[] = $promotion;
            $promotion->setProductSaleChannel($this);
        }

        return $this;
    }

    public function removePromotion(Promotion $promotion): self
    {
        if ($this->promotions->removeElement($promotion)) {
            // set the owning side to null (unless already changed)
            if ($promotion->getProductSaleChannel() === $this) {
                $promotion->setProductSaleChannel(null);
            }
        }

        return $this;
    }


     /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        /*$dates=[];
        foreach ($this->promotions as $promotion) {
            if ($promotion->getBeginDate() &&  $promotion->getEndDate()) {
                foreach ($dates as $date) {
                    if ($promotion->getBeginDate()->format('YmdHi')< $date['end'] && $promotion->getEndDate()->format('YmdHi') > $date['start']) {

                        $context->buildViolation('2 promotions for sale channel '.$this.' are together ('.$promotion->getBeginDate()->format('d-m-Y H:i').'-'.$promotion->getEndDate()->format('d-m-Y H:i').') and ('.$date['startHuman'].'-'.$date['endHuman'].')')
                            ->atPath('promotions')
                            ->addViolation();
                    }
                }
                $dates[]=[
                    'start' =>$promotion->getBeginDate()->format('YmdHi'),
                    'startHuman' =>$promotion->getBeginDate()->format('d-m-Y H:i'),
                    'end' =>$promotion->getEndDate()->format('YmdHi'),
                    'endHuman' =>$promotion->getBeginDate()->format('d-m-Y H:i'),
                ];
            }
        }*/
       
        if ($this->price && $this->price < ((100 + self::TX_MARGIN)/100) * $this->getProduct()->getUnitCost()) {
            $context->buildViolation('You do a selling price which is only '.self::TX_MARGIN.'% more than product cost '.$this->getProduct()->getUnitCost().'€')
                        ->atPath('price')
                        ->addViolation();
        }
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

    public function getRecommendedPrice(): ?float
    {
        return $this->recommendedPrice;
    }

    public function setRecommendedPrice(?float $recommendedPrice): self
    {
        $this->recommendedPrice = $recommendedPrice;

        return $this;
    }

    public function getEstimatedCommission(): ?float
    {
        return $this->estimatedCommission;
    }

    public function setEstimatedCommission(?float $estimatedCommission): self
    {
        $this->estimatedCommission = $estimatedCommission;

        return $this;
    }

    public function getEstimatedShipping(): ?float
    {
        return $this->estimatedShipping;
    }

    public function setEstimatedShipping(?float $estimatedShipping): self
    {
        $this->estimatedShipping = $estimatedShipping;

        return $this;
    }

    public function getEstimatedCommissionPercent(): ?float
    {
        return $this->estimatedCommissionPercent;
    }

    public function setEstimatedCommissionPercent(?float $estimatedCommissionPercent): self
    {
        $this->estimatedCommissionPercent = $estimatedCommissionPercent;

        return $this;
    }

    public function getEstimatedShippingPercent(): ?float
    {
        return $this->estimatedShippingPercent;
    }

    public function setEstimatedShippingPercent(?float $estimatedShippingPercent): self
    {
        $this->estimatedShippingPercent = $estimatedShippingPercent;

        return $this;
    }

    /**
     * @return Collection<int, ProductSaleChannelHistory>
     */
    public function getProductSaleChannelHistories(): Collection
    {
        return $this->productSaleChannelHistories;
    }

    public function addProductSaleChannelHistory(ProductSaleChannelHistory $productSaleChannelHistory): self
    {
        if (!$this->productSaleChannelHistories->contains($productSaleChannelHistory)) {
            $this->productSaleChannelHistories[] = $productSaleChannelHistory;
            $productSaleChannelHistory->setProductSaleChannel($this);
        }

        return $this;
    }

    public function removeProductSaleChannelHistory(ProductSaleChannelHistory $productSaleChannelHistory): self
    {
        if ($this->productSaleChannelHistories->removeElement($productSaleChannelHistory)) {
            // set the owning side to null (unless already changed)
            if ($productSaleChannelHistory->getProductSaleChannel() === $this) {
                $productSaleChannelHistory->setProductSaleChannel(null);
            }
        }

        return $this;
    }
}
