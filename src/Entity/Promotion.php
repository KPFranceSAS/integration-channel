<?php

namespace App\Entity;

use App\Entity\ProductLogEntry;
use App\Entity\ProductSaleChannel;
use App\Helper\Traits\TraitTimeUpdated;
use App\Helper\Utils\DatetimeUtils;
use DateInterval;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use IntlCalendar;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @Gedmo\Loggable(logEntryClass=ProductLogEntry::class)
 */
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Promotion implements \Stringable
{
    use TraitTimeUpdated;

    final public const TYPE_PERCENT = 'percent';
    final public const TYPE_FIXED = 'fixed';


    final public const FREQUENCY_CONTINUE = 'continuous';
    final public const FREQUENCY_WEEKEND = 'weekend';
    final public const FREQUENCY_TIMETOTIME = 'time';

    final public const TYPES = [self::TYPE_FIXED, self::TYPE_PERCENT];

    final public const FREQUENCIES = [self::FREQUENCY_CONTINUE, self::FREQUENCY_WEEKEND, self::FREQUENCY_TIMETOTIME];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * @Gedmo\Versioned
     */
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $beginDate = null;

    /**
     * @Gedmo\Versioned
     */
    #[Assert\GreaterThan(propertyPath: 'beginDate')]
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $endDate = null;
 

    /**
     * @Gedmo\Versioned
     */
    #[ORM\ManyToOne(targetEntity: ProductSaleChannel::class, inversedBy: 'promotions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?\App\Entity\ProductSaleChannel $productSaleChannel = null;

    /**
     * @Gedmo\Versioned
     */
    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\Choice(choices: Promotion::TYPES, message: 'Choose a valid type.')]
    private ?string $discountType=self::TYPE_PERCENT;

    /**
     * @Gedmo\Versioned
     */
    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\GreaterThan(0)]
    #[Assert\LessThan(50)]
    private ?float $percentageAmount = null;

    /**
     * @Gedmo\Versioned
     */
    #[ORM\Column(type: 'float', nullable: true)]
    #[Assert\GreaterThan(0)]
    private ?float $fixedAmount = null;

    /**
     * @Gedmo\Versioned
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $comment = null;

    /**
     * @Gedmo\Versioned
     */
    #[ORM\Column(type: 'boolean')]
    private ?bool $active=true;

    /**
     * @Gedmo\Versioned
     */
    #[ORM\Column(type: 'integer')]
    #[Assert\Range(min: 0, max: 10)]
    private ?int $priority=0;

    /**
     * @Gedmo\Versioned
     */
    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\Choice(choices: Promotion::FREQUENCIES, message: 'Choose a valid type.')]
    private ?string $frequency = self::FREQUENCY_CONTINUE;

    /**
     * @Gedmo\Versioned
     */
    #[ORM\Column(type: 'array', nullable: true)]
    private $weekDays = [];

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $searchableDescription = null;

    /**
     * @Gedmo\Versioned
     */
    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $beginHour = null;

    /**
     * @Gedmo\Versioned
     */
    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $endHour = null;

    /**
     * @Gedmo\Versioned
     */
    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $overrided=false;


       


    public function __construct()
    {
        $this->beginDate = new DateTime();
        $this->beginDate->add(new DateInterval('P1D'));
        $this->beginDate->setTime(18, 0);
        $this->endDate = new DateTime();
        $this->endDate->add(new DateInterval('P3D'));
        $this->endDate->setTime(23, 59);
    }


    public function isApplicable(DateTime $date): bool
    {
        if ($this->active == false) {
            return false;
        }
        if ($this->beginDate > $date || $this->endDate < $date) {
            return false;
        }
        if ($this->isWeekendFrequency()) {
            $dayOfWeek = $date->format('N');
            if ($dayOfWeek < 5 || ($dayOfWeek == 5 && $date->format('His')< '180000')) {
                return false;
            }
        } elseif ($this->isTimeToTimeFrequency()) {
            $dayOfWeek = $date->format('N');
            if (!in_array($dayOfWeek, $this->weekDays)) {
                return false;
            }
            if ($this->beginHour->format('His') > $date->format('His') || $this->endHour->format('His') < $date->format('His')) {
                return false;
            }
        }

        return true;
    }


    public function isBetterPromotionThan(Promotion $promotion): bool
    {
        $salePrice = $this->getPromotionPrice();
        $salePriceComparaison = $promotion->getPromotionPrice();
        return $salePrice < $salePriceComparaison;
    }


    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->cleanValues();
        $this->storeSearchableDescription();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->cleanValues();
        $this->storeSearchableDescription();
    }


    public function cleanValues()
    {
        if ($this->isFixedType()) {
            $this->percentageAmount = null;
        } else {
            $this->fixedAmount = null;
        }

        if (!$this->isTimeToTimeFrequency()) {
            $this->weekDays= [];
            $this->beginHour = null;
            $this->endHour = null;
        }
    }

    public function storeSearchableDescription()
    {
        $this->searchableDescription = $this->getProductName().' '.$this->getSaleChannelName().' '.$this->getPromotionDescriptionType().' '.$this->getPromotionDescriptionFrequency().' '.$this->comment;
    }


    public function getPromotionDescriptionFrequency()
    {
        if ($this->frequency == self::FREQUENCY_TIMETOTIME) {
            $days =[];
            foreach ($this->weekDays as $weekDay) {
                $days[] =  DatetimeUtils::getDayName($weekDay);
            }
            $frequency = 'Every '.implode(', ', $days).' from '.$this->beginHour->format('H:i').' to '.$this->endHour->format('H:i');
        } elseif ($this->frequency == self::FREQUENCY_WEEKEND) {
            $frequency =  'Every week end, from Friday 18:00 to Sunday 23:59';
        } else {
            $frequency =  'Continuous';
        }
        return $frequency.' during period from '.$this->beginDate->format('d-m-Y H:i').' to '.$this->endDate->format('d-m-Y H:i');
    }

    
    public function getPromotionDescriptionType()
    {
        return $this->discountType == self::TYPE_FIXED ?
                 'Fixed Price : '.$this->fixedAmount :
                 'Percent discount : '.$this->percentageAmount.'%';
    }

    public function getPromotionPrice()
    {
        return $this->discountType == self::TYPE_FIXED ?
                 $this->fixedAmount :
                 $this->productSaleChannel->getPrice() -  (($this->productSaleChannel->getPrice()*$this->percentageAmount)/100);
    }

    public function getCurrency()
    {
        return $this->productSaleChannel->getSaleChannel()->getCurrencyCode();
    }

    public function getProduct()
    {
        return $this->productSaleChannel->getProduct();
    }


    public function getProductName()
    {
        $product = $this->getProduct();
        return $product->getSku().' - '.$product->getDescription().' ['.$product->getBrandName().']';
    }

    public function getSaleChannel()
    {
        return $this->productSaleChannel->getSaleChannel();
    }

    public function getSaleChannelName()
    {
        $saleChannel = $this->getSaleChannel();
        return $saleChannel->getCode().' - '.$saleChannel->getName();
    }


    public function getRegularPrice()
    {
        return $this->productSaleChannel->getPrice();
    }


    public function __toString(): string
    {
        return $this->id ? 'from '.$this->beginDate->format('d/m/Y H:i').' to '.$this->endDate->format('d/m/Y H:i').' > ' .$this->getPromotionPrice().$this->getCurrency() : '...';
    }


    public function isPercentageType()
    {
        return $this->discountType == self::TYPE_PERCENT;
    }

    public function isFixedType()
    {
        return $this->discountType == self::TYPE_FIXED;
    }


    public function isContinuousFrequency()
    {
        return $this->frequency == self::FREQUENCY_CONTINUE;
    }

    public function isTimeToTimeFrequency()
    {
        return $this->frequency == self::FREQUENCY_TIMETOTIME;
    }

    public function isWeekendFrequency()
    {
        return $this->frequency == self::FREQUENCY_WEEKEND;
    }



    

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload)
    {
        $path = $this->isPercentageType() ? 'percentageAmount' : 'fixedAmount';

        if ($this->isPercentageType() && !$this->percentageAmount) {
            $context->buildViolation('You must define the percentage of promotion')
                ->atPath('percentageAmount')
                ->addViolation();
        }

        if ($this->isFixedType() && !$this->fixedAmount) {
            $context->buildViolation('You must define the fixed amount of promotion')
                ->atPath('fixedAmount')
                ->addViolation();
        }


        if (!$this->productSaleChannel->getPrice()) {
            $context->buildViolation('You cannot add promotions if price is not defined on '.$this->productSaleChannel)
                ->atPath($path)
                ->addViolation();
        } else {
            if ($this->productSaleChannel->getPrice() < $this->getPromotionPrice()) {
                $context->buildViolation('Your final price is greater than your normal prices on '.$this->productSaleChannel)
                ->atPath($path)
                ->addViolation();
            } else {
                if ($this->overrided === true) {
                } else {
                    if ($this->isFixedType()) {
                        $price = $this->productSaleChannel->getPrice();
                        $discountPrice = $this->getPromotionPrice();
                        $discount = ($price-$discountPrice)/$price;
                        if ($discount>0.5) {
                            $context->buildViolation('You do promotion of 50% and more on '.$this->productSaleChannel)
                            ->atPath($path)
                            ->addViolation();
                        }
                    }
                }
            }

            if ($this->overrided === true) {
            } else {
                if ($this->getPromotionPrice() && $this->getPromotionPrice() < ((100 + ProductSaleChannel::TX_MARGIN)/100) * $this->getProduct()->getUnitCost()) {
                    $context->buildViolation('You do promotion on final price '.$this->getPromotionPrice().' where result have only '.ProductSaleChannel::TX_MARGIN.'% more than product cost ('.$this->getProduct()->getUnitCost().')')
                                ->atPath($path)
                                ->addViolation();
                }
            }
        }


        if ($this->frequency == self::FREQUENCY_TIMETOTIME) {
            if (count($this->weekDays)==0) {
                $context->buildViolation('You must select at least one day for the select frequency : time')
                ->atPath("weekDays")
                ->addViolation();
            }

            if (!$this->endHour) {
                $context->buildViolation('You must define the end of hour')
                ->atPath('endHour')
                ->addViolation();
            }
            if (!$this->beginHour) {
                $context->buildViolation('You must define the begin of hour')
                ->atPath('beginHour')
                ->addViolation();
            }
            if ($this->endHour && $this->beginHour && $this->beginHour >= $this->endHour) {
                $context->buildViolation('You must define the end of hour later the befin hour')
                ->atPath('endHour')
                ->addViolation();
            }
        }
    }


    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBeginDate(): ?DateTimeInterface
    {
        return $this->beginDate;
    }

    public function setBeginDate(DateTimeInterface $beginDate): self
    {
        $this->beginDate = $beginDate;

        return $this;
    }

    public function getEndDate(): ?DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

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

    public function getDiscountType(): ?string
    {
        return $this->discountType;
    }

    public function setDiscountType(string $discountType): self
    {
        $this->discountType = $discountType;

        return $this;
    }


    public function getFixedAmount(): ?float
    {
        return $this->fixedAmount;
    }

    public function setFixedAmount(?float $fixedAmount): self
    {
        $this->fixedAmount = $fixedAmount;

        return $this;
    }

    public function getPercentageAmount(): ?float
    {
        return $this->percentageAmount;
    }

    public function setPercentageAmount(?float $percentageAmount): self
    {
        $this->percentageAmount = $percentageAmount;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getFrequency(): ?string
    {
        return $this->frequency;
    }

    public function setFrequency(string $frequency): self
    {
        $this->frequency = $frequency;

        return $this;
    }

    public function getWeekDays(): ?array
    {
        return $this->weekDays;
    }

    public function setWeekDays(?array $weekDays): self
    {
        $this->weekDays = $weekDays;

        return $this;
    }

    public function getSearchableDescription(): ?string
    {
        return $this->searchableDescription;
    }

    public function setSearchableDescription(?string $searchableDescription): self
    {
        $this->searchableDescription = $searchableDescription;

        return $this;
    }

    public function getBeginHour(): ?DateTimeInterface
    {
        return $this->beginHour;
    }

    public function setBeginHour(?DateTimeInterface $beginHour): self
    {
        $this->beginHour = $beginHour;

        return $this;
    }

    public function getEndHour(): ?DateTimeInterface
    {
        return $this->endHour;
    }

    public function setEndHour(?DateTimeInterface $endHour): self
    {
        $this->endHour = $endHour;

        return $this;
    }

    public function isOverrided(): ?bool
    {
        return $this->overrided;
    }

    public function setOverrided(?bool $overrided): self
    {
        $this->overrided = $overrided;

        return $this;
    }
}
