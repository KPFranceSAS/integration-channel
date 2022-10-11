<?php

namespace App\Entity;

use App\Helper\Traits\TraitTimeUpdated;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity("code")
 *
 */
class IntegrationChannel
{


    public const CHANNEL_CHANNELADVISOR = 'CHANNELADVISOR';
    public const CHANNEL_ALIEXPRESS = 'ALIEXPRESS';
    public const CHANNEL_FITBITEXPRESS = 'FITBITEXPRESS';
    public const CHANNEL_OWLETCARE = 'OWLETCARE';
    public const CHANNEL_MINIBATT = 'MINIBATT';
    public const CHANNEL_FLASHLED = 'FLASHLED';
    public const CHANNEL_FITBITCORPORATE = 'FITBITCORPORATE';
    public const CHANNEL_ARISE = 'ARISE';


    use TraitTimeUpdated;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $code;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active=false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $orderSync=false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $stockSync=false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $priceSync=false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $productSync=false;


    public function __toString()
    {
        return $this->code;
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
}
