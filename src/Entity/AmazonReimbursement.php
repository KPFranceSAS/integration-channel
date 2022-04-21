<?php

namespace App\Entity;

use App\Helper\Utils\DatetimeUtils;
use App\Helper\Utils\ExchangeRateCalculator;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class AmazonReimbursement
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     *  @Groups({"export_order"})
     */
    private $approvalDate;

    /**
     * @ORM\Column(type="string", length=255)
     *  @Groups({"export_order"})
     */
    private $reimbursementId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private $caseId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private $amazonOrderId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private $reason;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private $sku;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private $fnsku;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private $asin;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private $conditionItem;

    /**
     * @ORM\Column(type="string", length=255)
     *  @Groups({"export_order"})
     */
    private $currencyUnit;

    /**
     * @ORM\Column(type="float", nullable=true)
     *  @Groups({"export_order"})
     */
    private $amountPerUnit;

    /**
     * @ORM\Column(type="float", nullable=true)
     *  @Groups({"export_order"})
     */
    private $amountTotal;


    /**
     * @ORM\Column(type="float", nullable=true)
     *  @Groups({"export_order"})
     */
    private $amountPerUnitCurrency;

    /**
     * @ORM\Column(type="float", nullable=true)
     *  @Groups({"export_order"})
     */
    private $amountTotalCurrency;

    /**
     * @ORM\Column(type="integer")
     *  @Groups({"export_order"})
     */
    private $quantityReimbursedCash;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *  @Groups({"export_order"})
     */
    private $quantityReimbursedInventory;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"export_order"})
     */
    private $quantityReimbursedTotal;

    /**
     * @ORM\ManyToOne(targetEntity=AmazonReimbursement::class)
     */
    private $originalReimbursement;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class)
     */
    private $product;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;



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
                    $this->{$attribute . 'Currency'} = $valueFormate > 0 ? $valueFormate : null;
                    $this->{$attribute} =  $valueFormate > 0 ? round($calculator->getConvertedAmountDate($valueFormate, $this->currencyUnit, $this->approvalDate), 2) : null;
                } elseif (in_array($key, [
                    "quantity-reimbursed-cash-per-unit",
                    "quantity-reimbursed-inventory",
                    "quantity-reimbursed-total",
                ])) {
                    $this->{$attribute} = intval($value);
                } else {
                    $this->{$attribute} = strlen($value) > 0 ? $value : null;
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
        return lcfirst(str_replace($separator, '', ucwords($input, $separator)));
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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
}
