<?php

namespace App\Entity;

use App\Helper\Traits\TraitTimeUpdated;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity('code')]
class IntegrationChannel implements \Stringable
{
    final public const CHANNEL_CHANNELADVISOR = 'CHANNELADVISOR';
    final public const CHANNEL_ALIEXPRESS = 'ALIEXPRESS';
    final public const CHANNEL_FITBITEXPRESS = 'FITBITEXPRESS';
    final public const CHANNEL_OWLETCARE = 'OWLETCARE';
    final public const CHANNEL_MINIBATT = 'MINIBATT';
    final public const CHANNEL_FLASHLED = 'FLASHLED';
    final public const CHANNEL_FITBITCORPORATE = 'FITBITCORPORATE';
    final public const CHANNEL_ARISE = 'ARISE';
    final public const CHANNEL_SONOS_ARISE='SONOS_ARISE';
    final public const CHANNEL_AMAZFIT_ARISE='AMAZFIT_ARISE';
    final public const CHANNEL_IMOU_ARISE='IMOU_ARISE';
    final public const CHANNEL_DECATHLON='DECATHLON';
    final public const CHANNEL_BOULANGER='BOULANGER';
    final public const CHANNEL_LEROYMERLIN='LEROYMERLIN';
    final public const CHANNEL_MANOMANO_FR='MANOMANO_FR';
    final public const CHANNEL_MANOMANO_ES='MANOMANO_ES';
    final public const CHANNEL_MANOMANO_DE='MANOMANO_DE';
    final public const CHANNEL_MANOMANO_IT='MANOMANO_IT';
    final public const CHANNEL_CDISCOUNT='CDISCOUNT';
    final public const CHANNEL_PAXUK='PAXUK';
    final public const CHANNEL_PAXEU='PAXEU';
    final public const CHANNEL_MEDIAMARKT='MEDIAMARKT';
    final public const CHANNEL_FNAC_FR='FNAC_FR';
    final public const CHANNEL_FNAC_ES='FNAC_ES';
    final public const CHANNEL_DARTY_FR='DARTY_FR';
    final public const CHANNEL_REENCLE='REENCLE';
    final public const CHANNEL_WORTEN='WORTEN';
    final public const CHANNEL_PCCOMPONENTES='PCCOMPONENTES';
    final public const CHANNEL_CARREFOUR_ES='CARREFOUR_ES';
    final public const CHANNEL_CORTEINGLES='CORTEINGLES';
    final public const CHANNEL_AMAZON_FR='AMAZON_FR';

    use TraitTimeUpdated;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    private ?string $code = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private ?bool $active=false;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private ?bool $orderSync=false;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private ?bool $stockSync=false;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private ?bool $priceSync=false;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private ?bool $productSync=false;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\SaleChannel>
     */
    #[ORM\OneToMany(targetEntity: SaleChannel::class, mappedBy: 'integrationChannel')]
    private \Doctrine\Common\Collections\Collection $saleChannels;

    public function __construct()
    {
        $this->saleChannels = new ArrayCollection();
    }


    public function __toString(): string
    {
        return (string) $this->code;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function isOrderSync(): ?bool
    {
        return $this->orderSync;
    }

    public function setOrderSync(bool $orderSync): self
    {
        $this->orderSync = $orderSync;

        return $this;
    }

    public function isStockSync(): ?bool
    {
        return $this->stockSync;
    }

    public function setStockSync(bool $stockSync): self
    {
        $this->stockSync = $stockSync;

        return $this;
    }

    public function isPriceSync(): ?bool
    {
        return $this->priceSync;
    }

    public function setPriceSync(bool $priceSync): self
    {
        $this->priceSync = $priceSync;

        return $this;
    }

    public function isProductSync(): ?bool
    {
        return $this->productSync;
    }

    public function setProductSync(bool $productSync): self
    {
        $this->productSync = $productSync;

        return $this;
    }

    /**
     * @return Collection<int, SaleChannel>
     */
    public function getSaleChannels(): Collection
    {
        return $this->saleChannels;
    }

    public function addSaleChannel(SaleChannel $saleChannel): self
    {
        if (!$this->saleChannels->contains($saleChannel)) {
            $this->saleChannels[] = $saleChannel;
            $saleChannel->setIntegrationChannel($this);
        }

        return $this;
    }

    public function removeSaleChannel(SaleChannel $saleChannel): self
    {
        if ($this->saleChannels->removeElement($saleChannel)) {
            // set the owning side to null (unless already changed)
            if ($saleChannel->getIntegrationChannel() === $this) {
                $saleChannel->setIntegrationChannel(null);
            }
        }

        return $this;
    }
}
