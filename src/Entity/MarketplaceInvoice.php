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
class MarketplaceInvoice
{

    use TraitTimeUpdated;

    use TraitLoggable;


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $documentNumber = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $documentDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $erpDocumentNumber = null;

    #[ORM\Column(length: 255)]
    private ?string $vendorNumber = null;

    #[ORM\Column(length: 255)]
    private ?string $company = null;

    #[ORM\Column]
    private ?float $totalAmountWithTax = null;

    #[ORM\Column(nullable: true)]
    private ?float $totalAmountTax = null;

    #[ORM\Column(nullable: true)]
    private ?float $totalAmountNoTax = null;

    #[ORM\OneToMany(mappedBy: 'marketplaceInvoice', targetEntity: MarketplaceInvoiceLine::class, orphanRemoval: true ,cascade : ["persist"] )]
    private Collection $marketplaceInvoiceLines;

    #[ORM\ManyToOne(inversedBy: 'marketplaceInvoices')]
    private ?Settlement $settlement = null;

    #[ORM\Column(length: 255)]
    private ?string $channel = null;

    public function __construct()
    {
        $this->marketplaceInvoiceLines = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocumentNumber(): ?string
    {
        return $this->documentNumber;
    }

    public function setDocumentNumber(?string $documentNumber): static
    {
        $this->documentNumber = $documentNumber;

        return $this;
    }

    public function getDocumentDate(): ?\DateTimeInterface
    {
        return $this->documentDate;
    }

    public function setDocumentDate(?\DateTimeInterface $documentDate): static
    {
        $this->documentDate = $documentDate;

        return $this;
    }

    public function getErpDocumentNumber(): ?string
    {
        return $this->erpDocumentNumber;
    }

    public function setErpDocumentNumber(?string $erpDocumentNumber): static
    {
        $this->erpDocumentNumber = $erpDocumentNumber;

        return $this;
    }

    public function getVendorNumber(): ?string
    {
        return $this->vendorNumber;
    }

    public function setVendorNumber(string $vendorNumber): static
    {
        $this->vendorNumber = $vendorNumber;

        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(string $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getTotalAmountWithTax(): ?float
    {
        return $this->totalAmountWithTax;
    }

    public function setTotalAmountWithTax(float $totalAmountWithTax): static
    {
        $this->totalAmountWithTax = $totalAmountWithTax;

        return $this;
    }

    public function getTotalAmountTax(): ?float
    {
        return $this->totalAmountTax;
    }

    public function setTotalAmountTax(?float $totalAmountTax): static
    {
        $this->totalAmountTax = $totalAmountTax;

        return $this;
    }

    public function getTotalAmountNoTax(): ?float
    {
        return $this->totalAmountNoTax;
    }

    public function setTotalAmountNoTax(?float $totalAmountNoTax): static
    {
        $this->totalAmountNoTax = $totalAmountNoTax;

        return $this;
    }

    /**
     * @return Collection<int, MarketplaceInvoiceLine>
     */
    public function getMarketplaceInvoiceLines(): Collection
    {
        return $this->marketplaceInvoiceLines;
    }

    public function addMarketplaceInvoiceLine(MarketplaceInvoiceLine $marketplaceInvoiceLine): static
    {
        if (!$this->marketplaceInvoiceLines->contains($marketplaceInvoiceLine)) {
            $this->marketplaceInvoiceLines->add($marketplaceInvoiceLine);
            $marketplaceInvoiceLine->setMarketplaceInvoice($this);
        }

        return $this;
    }

    public function removeMarketplaceInvoiceLine(MarketplaceInvoiceLine $marketplaceInvoiceLine): static
    {
        if ($this->marketplaceInvoiceLines->removeElement($marketplaceInvoiceLine)) {
            // set the owning side to null (unless already changed)
            if ($marketplaceInvoiceLine->getMarketplaceInvoice() === $this) {
                $marketplaceInvoiceLine->setMarketplaceInvoice(null);
            }
        }

        return $this;
    }

    public function getSettlement(): ?Settlement
    {
        return $this->settlement;
    }

    public function setSettlement(?Settlement $settlement): static
    {
        $this->settlement = $settlement;

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
}
