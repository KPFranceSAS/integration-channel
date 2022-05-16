<?php

namespace App\Entity;


use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class Product
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * 
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"export_product"})
     */
    private $sku;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_product"})
     */
    private $asin;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_product"})
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $comments;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"export_product"})
     */
    private $active = true;

    /**
     * @ORM\ManyToOne(targetEntity=Brand::class, inversedBy="products")
     */
    private $brand;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_product"})
     */
    private $fnsku;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="products")
     */
    private $category;


    /**
     * @Groups({"export_product"})
     */
    public function getProductId()
    {
        return $this->getId();
    }

    /**
     * 
     * @Groups({"export_product"})
     */
    public function getBrandName()
    {
        return $this->brand ? $this->brand->getName() : 'NO BRAND';
    }


    /**
     * 
     * @Groups({"export_product"})
     */
    public function getCategoryName()
    {
        return $this->category ? $this->category->getName() : 'NO CATEGORY';
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

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(string $sku): self
    {
        $this->sku = $sku;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): self
    {
        $this->comments = $comments;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(?Brand $brand): self
    {
        $this->brand = $brand;

        return $this;
    }



    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

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
}
