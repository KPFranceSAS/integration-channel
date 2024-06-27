<?php

namespace App\Entity;

use App\Helper\Traits\TraitTimeUpdated;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Brand implements \Stringable
{
    final public const DEFAULT_BUFFER = 10;


    use TraitTimeUpdated;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private ?bool $active = true;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Product>
     */
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'brand')]
    private \Doctrine\Common\Collections\Collection $products;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    #[Assert\GreaterThanOrEqual(0)]
    private ?int $stockBuffer=self::DEFAULT_BUFFER;

    #[ORM\ManyToMany(targetEntity: SaleChannel::class, inversedBy: 'brands')]
    private Collection $saleChannels;

    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->saleChannels = new ArrayCollection();
    }

  
    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
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



    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }


    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
            $product->setBrand($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getBrand() === $this) {
                $product->setBrand(null);
            }
        }

        return $this;
    }

    public function getStockBuffer(): ?int
    {
        return $this->stockBuffer;
    }

    public function setStockBuffer(?int $stockBuffer): self
    {
        $this->stockBuffer = $stockBuffer;

        return $this;
    }

    /**
     * @return Collection<int, SaleChannel>
     */
    public function getSaleChannels(): Collection
    {
        return $this->saleChannels;
    }

    public function addSaleChannel(SaleChannel $saleChannel): static
    {
        if (!$this->saleChannels->contains($saleChannel)) {
            $this->saleChannels->add($saleChannel);
        }

        return $this;
    }

    public function removeSaleChannel(SaleChannel $saleChannel): static
    {
        $this->saleChannels->removeElement($saleChannel);

        return $this;
    }
}
