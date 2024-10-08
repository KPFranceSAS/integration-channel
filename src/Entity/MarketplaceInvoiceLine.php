<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\HasLifecycleCallbacks]
class MarketplaceInvoiceLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column]
    private ?float $totalAmountWithTax = null;

    #[ORM\Column(nullable: true)]
    private ?float $totalAmountTax = null;

    #[ORM\Column(nullable: true)]
    private ?float $totalAmountNoTax = null;

    #[ORM\ManyToOne(inversedBy: 'marketplaceInvoiceLines')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MarketplaceInvoice $marketplaceInvoice = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

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

    public function getMarketplaceInvoice(): ?MarketplaceInvoice
    {
        return $this->marketplaceInvoice;
    }

    public function setMarketplaceInvoice(?MarketplaceInvoice $marketplaceInvoice): static
    {
        $this->marketplaceInvoice = $marketplaceInvoice;

        return $this;
    }
}
