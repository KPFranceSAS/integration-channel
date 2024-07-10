<?php

namespace App\Helper\FormClass;

use App\Entity\Promotion;
use DateInterval;
use DateTime;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;


class MultiPromotion 
{
       
    public ?\DateTimeInterface $beginDate = null;

    #[Assert\GreaterThan(propertyPath: 'beginDate')]
    public ?\DateTimeInterface $endDate = null;
 
    #[Assert\Choice(choices: Promotion::TYPES, message: 'Choose a valid type.')]
    public ?string $discountType=Promotion::TYPE_PERCENT;

    #[Assert\GreaterThan(0)]
    #[Assert\LessThan(70)]
    public ?float $percentageAmount = null;

    #[Assert\GreaterThan(0)]
    public ?float $fixedAmount = null;
    
    #[Assert\Length(max: 255)]
    public ?string $comment = null;

    public ?bool $active=true;

    #[Assert\Range(min: 0, max: 10)]
    public ?int $priority=0;

    #[Assert\Choice(choices: Promotion::FREQUENCIES, message: 'Choose a valid type.')]
    public ?string $frequency = Promotion::FREQUENCY_CONTINUE;

    public $weekDays = [];

    public $products = [];

    public $saleChannels = [];

    public $promotions = [];

    public ?\DateTimeInterface $beginHour = null;

    public ?\DateTimeInterface $endHour = null;

    public ?bool $overrided=false;

       


    public function __construct()
    {
        $this->beginDate = new DateTime();
        $this->beginDate->add(new DateInterval('P1D'));
        $this->beginDate->setTime(18, 0);
        $this->endDate = new DateTime();
        $this->endDate->add(new DateInterval('P3D'));
        $this->endDate->setTime(23, 59);
    }



    public function generatePromotions(){
        $this->promotions = [];
        foreach($this->products as $product){
            foreach($this->saleChannels as $saleChannel){
                $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());
                $promotion = new Promotion();
                $productMarketplace->addPromotion($promotion);
                $promotion->setBeginDate($this->beginDate);
                $promotion->setOverrided($this->overrided);
                $promotion->setEndDate($this->endDate);
                $promotion->setDiscountType($this->discountType);
                if ($this->discountType==Promotion::TYPE_PERCENT) {
                    $promotion->setPercentageAmount(floatval($this->percentageAmount));
                } else {
                    $promotion->setFixedAmount(floatval($this->fixedAmount));
                }
                $promotion->setComment($this->comment);
                $promotion->setFrequency($this->frequency);
                if ($this->frequency==Promotion::FREQUENCY_TIMETOTIME) {
                    $promotion->setBeginHour($this->beginHour);
                    $promotion->setEndHour($this->endHour);
                    $promotion->setWeekDays($this->weekDays);
                }
                $promotion->setPriority((int)$this->priority);
                $promotion->cleanValues();
                $this->promotions[]=$promotion;
            }
        }
        return $this->promotions;
    }

    public function isPercentageType()
    {
        return $this->discountType == Promotion::TYPE_PERCENT;
    }

    public function isFixedType()
    {
        return $this->discountType == Promotion::TYPE_FIXED;
    }


    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload)
    {

        if ($this->isPercentageType() && is_null($this->percentageAmount)) {
            $context->buildViolation('You must define the percentage of promotion')
                ->atPath('percentageAmount')
                ->addViolation();
        }

        if ($this->isFixedType() && is_null($this->fixedAmount)) {
            $context->buildViolation('You must define the fixed amount of promotion')
                ->atPath('fixedAmount')
                ->addViolation();
        }
      
        


        if ($this->frequency == Promotion::FREQUENCY_TIMETOTIME) {
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


     
   
    
}
