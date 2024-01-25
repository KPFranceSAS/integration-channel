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
class FbaReturn implements \Stringable
{
    use TraitTimeUpdated;

    use TraitLoggable;
    
    final public const LOCALIZATION_FBA = 'FBA';

    final public const LOCALIZATION_CLIENT = 'CLIENT';

    final public const LOCALIZATION_FBA_REFURBISHED = 'FBA REFURBISHED';

    final public const LOCALIZATION_FBA_REIMBURSED = 'FBA REIMBURSED';


    final public const LOCALIZATION_BIARRITZ = 'BIARRITZ';

    final public const LOCALIZATION_LAROCA = 'LAROCA';

    final public const STATUS_WAITING_CUSTOMER = 0;

    final public const STATUS_RETURN_TO_FBA_NOTSELLABLE = 1;

    final public const STATUS_RETURN_TO_SALE = 2;

    final public const STATUS_RETURN_TO_BIARRITZ = 3;

    final public const STATUS_RETURN_TO_LAROCA = 4;

    final public const STATUS_SENT_TO_LAROCA = 5;

    final public const STATUS_WAITING_REIMBURSED_BY_FBA = 6;

    final public const STATUS_REIMBURSED_BY_FBA = 7;

    /**
    * @Groups({"export_order"})
    */
    public function getLocalizationLitteral()
    {
        return match ($this->localization) {
            self::LOCALIZATION_FBA => 'FBA Sellable',
            self::LOCALIZATION_FBA_REFURBISHED => 'FBA Unsellable',
            self::LOCALIZATION_FBA_REIMBURSED => 'FBA Reimbursed',
            self::LOCALIZATION_BIARRITZ => 'Biarritz',
            self::LOCALIZATION_LAROCA => 'La Roca',
            self::LOCALIZATION_CLIENT => 'Buyer',
            default => 'Unknow #'.$this->localization,
        };
    }

    /**
     * @Groups({"export_order"})
     */
    public function getStatusLitteral()
    {
        return match ($this->status) {
            self::STATUS_WAITING_CUSTOMER => 'Waiting for return',
            self::STATUS_RETURN_TO_FBA_NOTSELLABLE => 'Returned to FBA Unsellable',
            self::STATUS_RETURN_TO_SALE => 'Reintegrated to sale',
            self::STATUS_REIMBURSED_BY_FBA => 'Reimbursed by fba',
            self::STATUS_RETURN_TO_BIARRITZ => 'Return in Biarritz',
            self::STATUS_WAITING_REIMBURSED_BY_FBA => 'Waiting for reimbursed by FBA',
            self::STATUS_RETURN_TO_LAROCA => 'Receipted in La Roca',
            self::STATUS_SENT_TO_LAROCA => 'Sent to La Roca',
            default => 'UNknow #'.$this->status,
        };
    }

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;


    /**
     * @ORM\Column(type="integer")
     */
    private ?int $status = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private ?string $amazonOrderId = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $sellerOrderId = null;


    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $adjustmentId = null;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"export_order"})
     */
    private ?string $sku = null;

    /**
     * @ORM\Column(type="date_immutable")
     *  @Groups({"export_order"})
     */
    private $postedDate;


    /**
     * @ORM\ManyToOne(targetEntity=AmazonRemovalOrder::class, inversedBy="fbaReturns")
     */
    private ?\App\Entity\AmazonRemovalOrder $amazonRemoval = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private ?string $lpn = null;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class)
     */
    private ?\App\Entity\Product $product = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private ?string $marketplaceName = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private ?string $localization = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private ?string $amzProductStatus = null;

    /**
     * @ORM\OneToOne(targetEntity=AmazonReturn::class, cascade={"persist", "remove"})
     */
    private ?\App\Entity\AmazonReturn $amazonReturn = null;

    /**
     * @ORM\OneToOne(targetEntity=AmazonReimbursement::class, cascade={"persist", "remove"})
     */
    private ?\App\Entity\AmazonReimbursement $amazonReimbursement = null;

    /**
     * @ORM\Column(type="boolean")
     *  @Groups({"export_order"})
     */
    private ?bool $close = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private ?string $businessCentralDocument = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     *  @Groups({"export_order"})
     */
    private ?float $refundPrincipal = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     *  @Groups({"export_order"})
     */
    private ?float $refundCommission = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     *  @Groups({"export_order"})
     */
    private ?float $commissionOnRefund = null;


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


   

    public function __toString(): string
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
