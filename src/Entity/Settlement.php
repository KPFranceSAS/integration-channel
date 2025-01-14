<?php

namespace App\Entity;

use App\Helper\Traits\TraitLoggable;
use App\Helper\Traits\TraitTimeUpdated;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\HasLifecycleCallbacks]
class Settlement
{

    public const CREATION =0;
    public const CREATED = 1;


    use TraitTimeUpdated;

    use TraitLoggable;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $postedDate = null;

    #[ORM\OneToMany(mappedBy: 'settlement', targetEntity: SettlementTransaction::class, orphanRemoval: true, cascade : ["persist"])]
    private Collection $settlementTransactions;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $number = null;

    #[ORM\Column]
    private ?float $totalAmount = null;

    #[ORM\Column]
    private ?float $totalCommissionsWithTax = 0;

    #[ORM\Column]
    private ?float $totalRefundCommisionsWithTax = 0;

    #[ORM\Column]
    private ?float $totalOrders = 0;

    #[ORM\Column]
    private ?float $totalRefunds = 0;

    #[ORM\Column]
    private ?float $totalSubscriptions = 0;

    #[ORM\Column]
    private ?float $totalTransfer = null;

    #[ORM\Column(length: 255)]
    private ?string $channel = null;

    #[ORM\Column]
    private ?int $status = null;

    /**
     * @var Collection<int, MarketplaceInvoice>
     */
    #[ORM\OneToMany(mappedBy: 'settlement', targetEntity: MarketplaceInvoice::class, cascade : ["persist"])]
    private Collection $marketplaceInvoices;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $internalId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bank = null;

    #[ORM\Column(nullable: true)]
    private ?float $adjustmentFees = null;

    #[ORM\Column(nullable: true)]
    private ?float $reservedFundFees = null;

    #[ORM\Column(nullable: true)]
    private ?float $retriedPayoutFees = null;

    public function __construct()
    {
        $this->settlementTransactions = new ArrayCollection();
        $this->marketplaceInvoices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getTotalFees(){
        return $this->adjustmentFees + $this->reservedFundFees + $this->retriedPayoutFees + $this->totalCommissionsWithTax + $this->totalRefundCommisionsWithTax;
    }


    public function getPostedDate(): ?\DateTimeInterface
    {
        return $this->postedDate;
    }

    public function setPostedDate(\DateTimeInterface $postedDate): static
    {
        $this->postedDate = $postedDate;

        return $this;
    }

    public function getTotalAmount(): ?float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(float $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    /**
     * @return Collection<int, SettlementTransaction>
     */
    public function getSettlementTransactions(): Collection
    {
        return $this->settlementTransactions;
    }

    public function addSettlementTransaction(SettlementTransaction $settlementTransaction): static
    {
        if (!$this->settlementTransactions->contains($settlementTransaction)) {
            $this->settlementTransactions->add($settlementTransaction);
            $settlementTransaction->setSettlement($this);
        }

        return $this;
    }

    public function removeSettlementTransaction(SettlementTransaction $settlementTransaction): static
    {
        if ($this->settlementTransactions->removeElement($settlementTransaction)) {
            // set the owning side to null (unless already changed)
            if ($settlementTransaction->getSettlement() === $this) {
                $settlementTransaction->setSettlement(null);
            }
        }

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getTotalCommissionsWithTax(): ?float
    {
        return $this->totalCommissionsWithTax;
    }

    public function setTotalCommissionsWithTax(float $totalCommissionsWithTax): static
    {
        $this->totalCommissionsWithTax = $totalCommissionsWithTax;

        return $this;
    }

    public function getTotalRefundCommisionsWithTax(): ?float
    {
        return $this->totalRefundCommisionsWithTax;
    }

    public function setTotalRefundCommisionsWithTax(float $totalRefundCommisionsWithTax): static
    {
        $this->totalRefundCommisionsWithTax = $totalRefundCommisionsWithTax;

        return $this;
    }

    public function getTotalOrders(): ?float
    {
        return $this->totalOrders;
    }

    public function setTotalOrders(float $totalOrders): static
    {
        $this->totalOrders = $totalOrders;

        return $this;
    }

    public function getTotalRefunds(): ?float
    {
        return $this->totalRefunds;
    }

    public function setTotalRefunds(float $totalRefunds): static
    {
        $this->totalRefunds = $totalRefunds;

        return $this;
    }

    public function getTotalSubscriptions(): ?float
    {
        return $this->totalSubscriptions;
    }

    public function setTotalSubscriptions(float $totalSubscriptions): static
    {
        $this->totalSubscriptions = $totalSubscriptions;

        return $this;
    }

    public function getTotalTransfer(): ?float
    {
        return $this->totalTransfer;
    }

    public function setTotalTransfer(float $totalTransfer): static
    {
        $this->totalTransfer = $totalTransfer;

        return $this;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function setChannel(string $channel): static
    {
        $this->channel = $channel;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, MarketplaceInvoice>
     */
    public function getMarketplaceInvoices(): Collection
    {
        return $this->marketplaceInvoices;
    }

    public function addMarketplaceInvoice(MarketplaceInvoice $marketplaceInvoice): static
    {
        if (!$this->marketplaceInvoices->contains($marketplaceInvoice)) {
            $this->marketplaceInvoices->add($marketplaceInvoice);
            $marketplaceInvoice->setSettlement($this);
        }

        return $this;
    }

    public function removeMarketplaceInvoice(MarketplaceInvoice $marketplaceInvoice): static
    {
        if ($this->marketplaceInvoices->removeElement($marketplaceInvoice)) {
            // set the owning side to null (unless already changed)
            if ($marketplaceInvoice->getSettlement() === $this) {
                $marketplaceInvoice->setSettlement(null);
            }
        }

        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getInternalId(): ?string
    {
        return $this->internalId;
    }

    public function setInternalId(?string $internalId): static
    {
        $this->internalId = $internalId;

        return $this;
    }

    public function getBank(): ?string
    {
        return $this->bank;
    }

    public function setBank(?string $bank): static
    {
        $this->bank = $bank;

        return $this;
    }

    public function getAdjustmentFees(): ?float
    {
        return $this->adjustmentFees;
    }

    public function setAdjustmentFees(?float $adjustmentFees): static
    {
        $this->adjustmentFees = $adjustmentFees;

        return $this;
    }

    public function getReservedFundFees(): ?float
    {
        return $this->reservedFundFees;
    }

    public function setReservedFundFees(?float $reservedFundFees): static
    {
        $this->reservedFundFees = $reservedFundFees;

        return $this;
    }

    public function getRetriedPayoutFees(): ?float
    {
        return $this->retriedPayoutFees;
    }

    public function setRetriedPayoutFees(?float $retriedPayoutFees): static
    {
        $this->retriedPayoutFees = $retriedPayoutFees;

        return $this;
    }
}
