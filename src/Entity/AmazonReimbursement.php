<?php

namespace App\Entity;

use App\Helper\Traits\TraitTimeUpdated;
use App\Helper\Utils\DatetimeUtils;
use App\Helper\Utils\ExchangeRateCalculator;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class AmazonReimbursement implements \Stringable
{
    use TraitTimeUpdated;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="datetime")
     *  @Groups({"export_order"})
     */
    private ?\DateTimeInterface $approvalDate = null;

    /**
     * @ORM\Column(type="string", length=255)
     *  @Groups({"export_order"})
     */
    private ?string $reimbursementId = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private ?string $caseId = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private ?string $amazonOrderId = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private ?string $reason = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private ?string $sku = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private ?string $fnsku = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private ?string $asin = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private ?string $conditionItem = null;

    /**
     * @ORM\Column(type="string", length=255)
     *  @Groups({"export_order"})
     */
    private ?string $currencyUnit = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     *  @Groups({"export_order"})
     */
    private ?float $amountPerUnit = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     *  @Groups({"export_order"})
     */
    private ?float $amountTotal = null;


    /**
     * @ORM\Column(type="float", nullable=true)
     *  @Groups({"export_order"})
     */
    private ?float $amountPerUnitCurrency = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     *  @Groups({"export_order"})
     */
    private ?float $amountTotalCurrency = null;

    /**
     * @ORM\Column(type="integer")
     *  @Groups({"export_order"})
     */
    private ?int $quantityReimbursedCash = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *  @Groups({"export_order"})
     */
    private ?int $quantityReimbursedInventory = null;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"export_order"})
     */
    private ?int $quantityReimbursedTotal = null;

    /**
     * @ORM\ManyToOne(targetEntity=AmazonReimbursement::class)
     */
    private ?\App\Entity\AmazonReimbursement $originalReimbursement = null;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class)
     */
    private ?\App\Entity\Product $product = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
    *  @Groups({"export_order"})
     */
    private ?string $marketplaceName = null;



    /**
     *  @Groups({"export_order"})
     */
    public function getAmazonOrderIdProductId()
    {
        return $this->product ? $this->amazonOrderId . '_' . $this->product->getId() :  $this->amazonOrderId . '_';
    }


    public function __toString(): string
    {
        return (string) ($this->product ? $this->amazonOrderId . ' ' . $this->product->getSku() :  $this->amazonOrderId);
    }

    /**
    *  @Groups({"export_order"})
    */
    public function getApprovalDateFormatYmd()
    {
        return $this->approvalDate->format('Y-m-d');
    }


    /**
    *  @Groups({"export_order"})
    */
    public function getApprovalDateFormatCalendar()
    {
        return $this->approvalDate->format('j/n/Y');
    }


    public function getLitteralPrice(): string
    {
        return ($this->amountTotalCurrency != $this->amountTotal) ? $this->amountTotalCurrency . ' GBP' : $this->amountTotalCurrency . ' EUR';
    }

    public function importData(ExchangeRateCalculator $calculator, array $reimbursementAmz)
    {

        foreach ($reimbursementAmz as $key => $value) {
            
            $attribute = $this->checkIfImportAttribute($key);
            if ($attribute) {
                if (in_array($key, ["approval-date"])) {
                    $this->{$attribute} = DatetimeUtils::transformFromIso8601($value);
                } elseif (in_array($key, [
                    "amount-per-unit",
                    "amount-total",
                ])) {
                    $valueFormate = round(floatval($value), 2);
                    $this->{$attribute . 'Currency'} = $valueFormate;
                    $this->{$attribute} = round($calculator->getConvertedAmountDate($valueFormate, $this->currencyUnit, $this->approvalDate), 2);
                } elseif (in_array($key, [
                    "quantity-reimbursed-cash",
                    "quantity-reimbursed-inventory",
                    "quantity-reimbursed-total",
                ])) {
                    $this->{$attribute} = intval($value);
                } else {
                    $this->{$attribute} = strlen((string) $value) > 0 ? $value : null;
                }
            }
        }

    }

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
    public function getOriginalReimbursementId()
    {
        return $this->originalReimbursement ? $this->originalReimbursement->getId() :  null;
    }



    private function checkIfImportAttribute($key)
    {
        if ($key == 'condition') {
            return 'conditionItem';
        } else {
            $attribute = $this->camelize($key);
            return property_exists($this, $attribute) ? $attribute : null;
        }
    }


    private function camelize($input, $separator = '-')
    {
        return lcfirst(str_replace($separator, '', ucwords((string) $input, $separator)));
    }

 

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApprovalDate(): ?\DateTimeInterface
    {
        return $this->approvalDate;
    }

    public function setApprovalDate(\DateTimeInterface $approvalDate): self
    {
        $this->approvalDate = $approvalDate;

        return $this;
    }

    public function getReimbursementId(): ?string
    {
        return $this->reimbursementId;
    }

    public function setReimbursementId(string $reimbursementId): self
    {
        $this->reimbursementId = $reimbursementId;

        return $this;
    }

    public function getCaseId(): ?string
    {
        return $this->caseId;
    }

    public function setCaseId(?string $caseId): self
    {
        $this->caseId = $caseId;

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

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;

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

    public function getFnsku(): ?string
    {
        return $this->fnsku;
    }

    public function setFnsku(?string $fnsku): self
    {
        $this->fnsku = $fnsku;

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

    public function getConditionItem(): ?string
    {
        return $this->conditionItem;
    }

    public function setConditionItem(?string $conditionItem): self
    {
        $this->conditionItem = $conditionItem;

        return $this;
    }

    public function getAmountPerUnit(): ?float
    {
        return $this->amountPerUnit;
    }

    public function setAmountPerUnit(?float $amountPerUnit): self
    {
        $this->amountPerUnit = $amountPerUnit;

        return $this;
    }

    public function getAmountTotal(): ?float
    {
        return $this->amountTotal;
    }

    public function setAmountTotal(?float $amountTotal): self
    {
        $this->amountTotal = $amountTotal;

        return $this;
    }

    public function getAmountPerUnitCurrency(): ?float
    {
        return $this->amountPerUnitCurrency;
    }

    public function setAmountPerUnitCurrency(?float $amountPerUnitCurrency): self
    {
        $this->amountPerUnitCurrency = $amountPerUnitCurrency;

        return $this;
    }

    public function getAmountTotalCurrency(): ?float
    {
        return $this->amountTotalCurrency;
    }

    public function setAmountTotalCurrency(?float $amountTotalCurrency): self
    {
        $this->amountTotalCurrency = $amountTotalCurrency;

        return $this;
    }

    public function getQuantityReimbursedCash(): ?int
    {
        return $this->quantityReimbursedCash;
    }

    public function setQuantityReimbursedCash(int $quantityReimbursedCash): self
    {
        $this->quantityReimbursedCash = $quantityReimbursedCash;

        return $this;
    }

    public function getQuantityReimbursedInventory(): ?int
    {
        return $this->quantityReimbursedInventory;
    }

    public function setQuantityReimbursedInventory(?int $quantityReimbursedInventory): self
    {
        $this->quantityReimbursedInventory = $quantityReimbursedInventory;

        return $this;
    }

    public function getQuantityReimbursedTotal(): ?int
    {
        return $this->quantityReimbursedTotal;
    }

    public function setQuantityReimbursedTotal(int $quantityReimbursedTotal): self
    {
        $this->quantityReimbursedTotal = $quantityReimbursedTotal;

        return $this;
    }

    

    public function getOriginalReimbursement(): ?self
    {
        return $this->originalReimbursement;
    }

    public function setOriginalReimbursement(?self $originalReimbursement): self
    {
        $this->originalReimbursement = $originalReimbursement;

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

    public function getCurrencyUnit(): ?string
    {
        return $this->currencyUnit;
    }

    public function setCurrencyUnit(string $currencyUnit): self
    {
        $this->currencyUnit = $currencyUnit;

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
}
