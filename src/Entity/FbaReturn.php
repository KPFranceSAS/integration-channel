<?php

namespace App\Entity;

use App\Helper\Traits\TraitLoggable;
use App\Helper\Traits\TraitTimeUpdated;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class FbaReturn
{
    use TraitTimeUpdated;

    use TraitLoggable;
    
    public const LOCALIZATION_FBA = 'FBA';

    public const LOCALIZATION_CLIENT = 'CLIENT';

    public const LOCALIZATION_FBA_REFURBISHED = 'FBA REFURBISHED';

    public const LOCALIZATION_FBA_REIMBURSED = 'FBA REIMBURSED';


    public const LOCALIZATION_BIARRITZ = 'BIARRITZ';

    public const LOCALIZATION_LAROCA = 'LAROCA';

    public const STATUS_WAITING_CUSTOMER = 0;

    public const STATUS_RETURN_TO_FBA_NOTSELLABLE = 1;

    public const STATUS_RETURN_TO_SALE = 2;

    public const STATUS_RETURN_TO_BIARRITZ = 3;

    public const STATUS_RETURN_TO_LAROCA = 4;

    public const STATUS_SENT_TO_LAROCA = 5;

    public const STATUS_WAITING_REIMBURSED_BY_FBA = 6;

    public const STATUS_REIMBURSED_BY_FBA = 7;

    /**
    * @Groups({"export_order"})
    */
    public function getLocalizationLitteral()
    {
        switch ($this->localization) {
            case self::LOCALIZATION_FBA:
                return 'FBA Sellable';
            case self::LOCALIZATION_FBA_REFURBISHED:
                return 'FBA Unsellable';
            case self::LOCALIZATION_FBA_REIMBURSED:
                return 'FBA Reimbursed';
            case self::LOCALIZATION_BIARRITZ:
                return 'Biarritz';
            case self::LOCALIZATION_LAROCA:
                return 'La Roca';
            case self::LOCALIZATION_CLIENT:
                return 'Buyer';
            default:
                return 'Unknow #'.$this->localization;
        }
    }

    /**
     * @Groups({"export_order"})
     */
    public function getStatusLitteral()
    {
        switch ($this->status) {
            case self::STATUS_WAITING_CUSTOMER:
                return 'Waiting for return';
            case self::STATUS_RETURN_TO_FBA_NOTSELLABLE:
                return 'Returned to FBA Unsellable';
            case self::STATUS_RETURN_TO_SALE:
                return 'Reintegrated to sale';
            case self::STATUS_REIMBURSED_BY_FBA:
                return 'Reimbursed by fba';
            case self::STATUS_RETURN_TO_BIARRITZ:
                return 'Return in Biarritz';
            case self::STATUS_WAITING_REIMBURSED_BY_FBA:
                return 'Waiting for reimbursed by FBA';
            case self::STATUS_RETURN_TO_LAROCA:
                return 'Receipted in La Roca';
            case self::STATUS_SENT_TO_LAROCA:
                return 'Sent to La Roca';
            default:
                return 'UNknow #'.$this->status;
        }
    }

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;


    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private $amazonOrderId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sellerOrderId;


    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $adjustmentId;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"export_order"})
     */
    private $sku;

    /**
     * @ORM\Column(type="date_immutable")
     *  @Groups({"export_order"})
     */
    private $postedDate;


    /**
     * @ORM\ManyToOne(targetEntity=AmazonRemovalOrder::class, inversedBy="fbaReturns")
     */
    private $amazonRemoval;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private $lpn;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class)
     */
    private $product;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private $marketplaceName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private $localization;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private $amzProductStatus;

    /**
     * @ORM\OneToOne(targetEntity=AmazonReturn::class, cascade={"persist", "remove"})
     */
    private $amazonReturn;

    /**
     * @ORM\OneToOne(targetEntity=AmazonReimbursement::class, cascade={"persist", "remove"})
     */
    private $amazonReimbursement;

    /**
     * @ORM\Column(type="boolean")
     *  @Groups({"export_order"})
     */
    private $close;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private $businessCentralDocument;

    /**
     * @ORM\Column(type="float", nullable=true)
     *  @Groups({"export_order"})
     */
    private $refundPrincipal;

    /**
     * @ORM\Column(type="float", nullable=true)
     *  @Groups({"export_order"})
     */
    private $refundCommission;

    /**
     * @ORM\Column(type="float", nullable=true)
     *  @Groups({"export_order"})
     */
    private $commissionOnRefund;


    /**
     *  @Groups({"export_order"})
     */
    public function getAmazonOrderIdProductId()
    {
        return $this->product ? $this->amazonOrderId . '_' . $this->product->getId() :  $this->amazonOrderId . '_';
    }

    /**
     *  @Groups({"export_order"})
     */
    public function getSkuProduct()
    {
        return $this->product ? $this->product->getSku() :  $this->sku ;
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
    public function getPostedDateFormatYmd()
    {
        return $this->postedDate->format('Y-m-d');
    }


    /**
    *  @Groups({"export_order"})
    */
    public function getPostedDateFormatCalendar()
    {
        return $this->postedDate->format('j/n/Y');
    }



    public function hasNotBeenReturnedToFba()
    {
        return !$this->amazonReimbursement && !$this->amazonReturn;
    }


   

    public function __toString()
    {
        return $this->amazonOrderId.' '.$this->sku;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

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

    public function getSellerOrderId(): ?string
    {
        return $this->sellerOrderId;
    }

    public function setSellerOrderId(?string $sellerOrderId): self
    {
        $this->sellerOrderId = $sellerOrderId;

        return $this;
    }

    public function getAdjustmentId(): ?string
    {
        return $this->adjustmentId;
    }

    public function setAdjustmentId(?string $adjustmentId): self
    {
        $this->adjustmentId = $adjustmentId;

        return $this;
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

    public function getPostedDate(): ?\DateTimeImmutable
    {
        return $this->postedDate;
    }

    public function setPostedDate(\DateTimeImmutable $postedDate): self
    {
        $this->postedDate = $postedDate;

        return $this;
    }



    public function getAmazonRemoval(): ?AmazonRemovalOrder
    {
        return $this->amazonRemoval;
    }

    public function setAmazonRemoval(?AmazonRemovalOrder $amazonRemoval): self
    {
        $this->amazonRemoval = $amazonRemoval;

        return $this;
    }

    public function getLpn(): ?string
    {
        return $this->lpn;
    }

    public function setLpn(?string $lpn): self
    {
        $this->lpn = $lpn;

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

    public function getMarketplaceName(): ?string
    {
        return $this->marketplaceName;
    }

    public function setMarketplaceName(?string $marketplaceName): self
    {
        $this->marketplaceName = $marketplaceName;

        return $this;
    }

    public function getLocalization(): ?string
    {
        return $this->localization;
    }

    public function setLocalization(?string $localization): self
    {
        $this->localization = $localization;

        return $this;
    }

    public function getAmzProductStatus(): ?string
    {
        return $this->amzProductStatus;
    }

    public function setAmzProductStatus(?string $amzProductStatus): self
    {
        $this->amzProductStatus = $amzProductStatus;

        return $this;
    }

    public function getAmazonReturn(): ?AmazonReturn
    {
        return $this->amazonReturn;
    }

    public function setAmazonReturn(?AmazonReturn $amazonReturn): self
    {
        $this->amazonReturn = $amazonReturn;

        return $this;
    }

    public function getAmazonReimbursement(): ?AmazonReimbursement
    {
        return $this->amazonReimbursement;
    }

    public function setAmazonReimbursement(?AmazonReimbursement $amazonReimbursement): self
    {
        $this->amazonReimbursement = $amazonReimbursement;

        return $this;
    }

    public function getClose(): ?bool
    {
        return $this->close;
    }

    public function setClose(bool $close): self
    {
        $this->close = $close;

        return $this;
    }

    public function getBusinessCentralDocument(): ?string
    {
        return $this->businessCentralDocument;
    }

    public function setBusinessCentralDocument(?string $businessCentralDocument): self
    {
        $this->businessCentralDocument = $businessCentralDocument;

        return $this;
    }

    public function getRefundPrincipal(): ?float
    {
        return $this->refundPrincipal;
    }

    public function setRefundPrincipal(?float $refundPrincipal): self
    {
        $this->refundPrincipal = $refundPrincipal;

        return $this;
    }

    public function getRefundCommission(): ?float
    {
        return $this->refundCommission;
    }

    public function setRefundCommission(?float $refundCommission): self
    {
        $this->refundCommission = $refundCommission;

        return $this;
    }

    public function getCommissionOnRefund(): ?float
    {
        return $this->commissionOnRefund;
    }

    public function setCommissionOnRefund(?float $commissionOnRefund): self
    {
        $this->commissionOnRefund = $commissionOnRefund;

        return $this;
    }
}
