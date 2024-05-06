<?php

namespace App\Entity;


use App\Helper\Traits\TraitTimeUpdated;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\HasLifecycleCallbacks]
class ProductTypeCategorizacion
{


    use TraitTimeUpdated;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $pimProductType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pimProductLabel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $decathlonCategory = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $leroymerlinCategory = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $boulangerCategory = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fnacDartyCategory = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mediamarktCategory = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $amazonCategory = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cdiscountCategory = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $manomanoCategory = null;

    #[ORM\Column]
    private ?int $countProducts = null;

    #[ORM\Column]
    private ?bool $existInPim = null;


    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPimProductType(): ?string
    {
        return $this->pimProductType;
    }

    public function setPimProductType(string $pimProductType): static
    {
        $this->pimProductType = $pimProductType;

        return $this;
    }

    public function getPimProductLabel(): ?string
    {
        return $this->pimProductLabel;
    }

    public function setPimProductLabel(?string $pimProductLabel): static
    {
        $this->pimProductLabel = $pimProductLabel;

        return $this;
    }

    public function getDecathlonCategory(): ?string
    {
        return $this->decathlonCategory;
    }

    public function setDecathlonCategory(?string $decathlonCategory): static
    {
        $this->decathlonCategory = $decathlonCategory;

        return $this;
    }

    public function getLeroymerlinCategory(): ?string
    {
        return $this->leroymerlinCategory;
    }

    public function setLeroymerlinCategory(?string $leroymerlinCategory): static
    {
        $this->leroymerlinCategory = $leroymerlinCategory;

        return $this;
    }

    public function getBoulangerCategory(): ?string
    {
        return $this->boulangerCategory;
    }

    public function setBoulangerCategory(?string $boulangerCategory): static
    {
        $this->boulangerCategory = $boulangerCategory;

        return $this;
    }

    public function getFnacDartyCategory(): ?string
    {
        return $this->fnacDartyCategory;
    }

    public function setFnacDartyCategory(?string $fnacDartyCategory): static
    {
        $this->fnacDartyCategory = $fnacDartyCategory;

        return $this;
    }

    public function getMediamarktCategory(): ?string
    {
        return $this->mediamarktCategory;
    }

    public function setMediamarktCategory(?string $mediamarktCategory): static
    {
        $this->mediamarktCategory = $mediamarktCategory;

        return $this;
    }

    public function getAmazonCategory(): ?string
    {
        return $this->amazonCategory;
    }

    public function setAmazonCategory(?string $amazonCategory): static
    {
        $this->amazonCategory = $amazonCategory;

        return $this;
    }

    public function getCdiscountCategory(): ?string
    {
        return $this->cdiscountCategory;
    }

    public function setCdiscountCategory(?string $cdiscountCategory): static
    {
        $this->cdiscountCategory = $cdiscountCategory;

        return $this;
    }

    public function getManomanoCategory(): ?string
    {
        return $this->manomanoCategory;
    }

    public function setManomanoCategory(?string $manomanoCategory): static
    {
        $this->manomanoCategory = $manomanoCategory;

        return $this;
    }

    public function getCountProducts(): ?int
    {
        return $this->countProducts;
    }

    public function setCountProducts(int $countProducts): static
    {
        $this->countProducts = $countProducts;

        return $this;
    }

    public function isExistInPim(): ?bool
    {
        return $this->existInPim;
    }

    public function setExistInPim(bool $existInPim): static
    {
        $this->existInPim = $existInPim;

        return $this;
    }

}
