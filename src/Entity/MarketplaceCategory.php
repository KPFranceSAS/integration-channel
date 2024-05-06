<?php

namespace App\Entity;

use App\Helper\Traits\TraitTimeUpdated;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\HasLifecycleCallbacks]
class MarketplaceCategory
{

    use TraitTimeUpdated;


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $path = null;

    #[ORM\Column(length: 255)]
    private ?string $marketplace = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getMarketplace(): ?string
    {
        return $this->marketplace;
    }

    public function setMarketplace(string $marketplace): static
    {
        $this->marketplace = $marketplace;

        return $this;
    }
}
