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

    #[ORM\Column(nullable: true)]
    private ?int $nbProductDecathlon = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbProductLeroymerlin = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbProductBoulanger = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbProductFnacDarty = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbProductMediamarkt = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbProductManomano = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbProductAmazon = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbProductCdiscount = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $amazonFrCategory = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $amazonDeCategory = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $amazonEsCategory = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $amazonItCategory = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $amazonUkCategory = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbProductAmazonEs = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbProductAmazonFr = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbProductAmazonDe = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbProductAmazonUk = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbProductAmazonIt = null;


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

    public function getNbProductDecathlon(): ?int
    {
        return $this->nbProductDecathlon;
    }

    public function setNbProductDecathlon(?int $nbProductDecathlon): static
    {
        $this->nbProductDecathlon = $nbProductDecathlon;

        return $this;
    }

    public function getNbProductLeroymerlin(): ?int
    {
        return $this->nbProductLeroymerlin;
    }

    public function setNbProductLeroymerlin(?int $nbProductLeroymerlin): static
    {
        $this->nbProductLeroymerlin = $nbProductLeroymerlin;

        return $this;
    }

    public function getNbProductBoulanger(): ?int
    {
        return $this->nbProductBoulanger;
    }

    public function setNbProductBoulanger(?int $nbProductBoulanger): static
    {
        $this->nbProductBoulanger = $nbProductBoulanger;

        return $this;
    }

    public function getNbProductFnacDarty(): ?int
    {
        return $this->nbProductFnacDarty;
    }

    public function setNbProductFnacDarty(?int $nbProductFnacDarty): static
    {
        $this->nbProductFnacDarty = $nbProductFnacDarty;

        return $this;
    }

    public function getNbProductMediamarkt(): ?int
    {
        return $this->nbProductMediamarkt;
    }

    public function setNbProductMediamarkt(?int $nbProductMediamarkt): static
    {
        $this->nbProductMediamarkt = $nbProductMediamarkt;

        return $this;
    }

    public function getNbProductManomano(): ?int
    {
        return $this->nbProductManomano;
    }

    public function setNbProductManomano(?int $nbProductManomano): static
    {
        $this->nbProductManomano = $nbProductManomano;

        return $this;
    }

    public function getNbProductAmazon(): ?int
    {
        return $this->nbProductAmazon;
    }

    public function setNbProductAmazon(?int $nbProductAmazon): static
    {
        $this->nbProductAmazon = $nbProductAmazon;

        return $this;
    }

    public function getNbProductCdiscount(): ?int
    {
        return $this->nbProductCdiscount;
    }

    public function setNbProductCdiscount(?int $nbProductCdiscount): static
    {
        $this->nbProductCdiscount = $nbProductCdiscount;

        return $this;
    }

    public function getAmazonFrCategory(): ?string
    {
        return $this->amazonFrCategory;
    }

    public function setAmazonFrCategory(?string $amazonFrCategory): static
    {
        $this->amazonFrCategory = $amazonFrCategory;

        return $this;
    }

    public function getAmazonDeCategory(): ?string
    {
        return $this->amazonDeCategory;
    }

    public function setAmazonDeCategory(?string $amazonDeCategory): static
    {
        $this->amazonDeCategory = $amazonDeCategory;

        return $this;
    }

    public function getAmazonEsCategory(): ?string
    {
        return $this->amazonEsCategory;
    }

    public function setAmazonEsCategory(?string $amazonEsCategory): static
    {
        $this->amazonEsCategory = $amazonEsCategory;

        return $this;
    }

    public function getAmazonItCategory(): ?string
    {
        return $this->amazonItCategory;
    }

    public function setAmazonItCategory(?string $amazonItCategory): static
    {
        $this->amazonItCategory = $amazonItCategory;

        return $this;
    }

    public function getAmazonUkCategory(): ?string
    {
        return $this->amazonUkCategory;
    }

    public function setAmazonUkCategory(?string $amazonUkCategory): static
    {
        $this->amazonUkCategory = $amazonUkCategory;

        return $this;
    }

    public function getNbProductAmazonEs(): ?int
    {
        return $this->nbProductAmazonEs;
    }

    public function setNbProductAmazonEs(?int $nbProductAmazonEs): static
    {
        $this->nbProductAmazonEs = $nbProductAmazonEs;

        return $this;
    }

    public function getNbProductAmazonFr(): ?int
    {
        return $this->nbProductAmazonFr;
    }

    public function setNbProductAmazonFr(?int $nbProductAmazonFr): static
    {
        $this->nbProductAmazonFr = $nbProductAmazonFr;

        return $this;
    }

    public function getNbProductAmazonDe(): ?int
    {
        return $this->nbProductAmazonDe;
    }

    public function setNbProductAmazonDe(?int $nbProductAmazonDe): static
    {
        $this->nbProductAmazonDe = $nbProductAmazonDe;

        return $this;
    }

    public function getNbProductAmazonUk(): ?int
    {
        return $this->nbProductAmazonUk;
    }

    public function setNbProductAmazonUk(?int $nbProductAmazonUk): static
    {
        $this->nbProductAmazonUk = $nbProductAmazonUk;

        return $this;
    }

    public function getNbProductAmazonIt(): ?int
    {
        return $this->nbProductAmazonIt;
    }

    public function setNbProductAmazonIt(?int $nbProductAmazonIt): static
    {
        $this->nbProductAmazonIt = $nbProductAmazonIt;

        return $this;
    }

}
