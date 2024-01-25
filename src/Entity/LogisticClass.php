<?php

namespace App\Entity;

use App\Helper\Traits\TraitTimeUpdated;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class LogisticClass implements \Stringable
{

    use TraitTimeUpdated;
    

    public function __toString(): string
    {
        return $this->code.' - '.$this->label.' - Between '.$this->minimumWeight.' and '.$this->maximumWeight.' kg';
    }

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $code = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $label = null;

    /**
     * @ORM\OneToMany(targetEntity=Product::class, mappedBy="logisticClass")
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Product>
     */
    private \Doctrine\Common\Collections\Collection $products;

    /**
     * @ORM\Column(type="float")
     */
    private ?float $minimumWeight = null;

    /**
     * @ORM\Column(type="float")
     */
    private ?float $maximumWeight = null;

    public function __construct()
    {
        $this->products = new ArrayCollection();
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

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): self
    {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
            $product->setLogisticClass($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): self
    {
        if ($this->products->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getLogisticClass() === $this) {
                $product->setLogisticClass(null);
            }
        }

        return $this;
    }

    public function getMinimumWeight(): ?float
    {
        return $this->minimumWeight;
    }

    public function setMinimumWeight(float $minimumWeight): self
    {
        $this->minimumWeight = $minimumWeight;

        return $this;
    }

    public function getMaximumWeight(): ?float
    {
        return $this->maximumWeight;
    }

    public function setMaximumWeight(?float $maximumWeight): self
    {
        $this->maximumWeight = $maximumWeight;

        return $this;
    }
}
