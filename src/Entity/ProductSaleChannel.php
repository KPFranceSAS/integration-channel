<?php

namespace App\Entity;

use App\Entity\Promotion;
use App\Helper\Traits\TraitTimeUpdated;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class ProductSaleChannel
{
    public const TX_MARGIN = 30;

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
     */
    private $enabled=false;

    /**
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity=Promotion::class, mappedBy="productSaleChannel", orphanRemoval=true, cascade={"persist","remove"})
     */
    private $promotions;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Assert\Expression(
     *   expression= "this.getEnabled() == false or (this.getEnabled() === true and value !== null)",
     *   message="You must specify the value if Enabled is activated"
     * )
     *  @Assert\GreaterThanOrEqual(0)
     */
    private $price;



    public function getSalePrice(DateTime $date)
    {
        $promotion = $this->getBestPromotionForDate($date);
        return $promotion ? $promotion->getPromotionPrice() : $this->price;
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

   


    public function __toString()
    {
        return $this->product->getBrand().' '.$this->product->getSku().' > '.$this->saleChannel->getName();
    }

    
    public function __construct()
    {
        $this->promotions = new ArrayCollection();
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
}
