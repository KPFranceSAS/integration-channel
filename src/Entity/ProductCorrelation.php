<?php

namespace App\Entity;

use App\Helper\Traits\TraitTimeUpdated;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity("skuUsed")
 *
 */
class ProductCorrelation
{
    use TraitTimeUpdated;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotIdenticalTo(propertyPath="skuErp", message="This value should be different from ERP one")
     */
    private $skuUsed;

    /**
     * @ORM\Column(type="string", length=255)
     *
     */
    private $skuErp;



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSkuUsed(): ?string
    {
        return $this->skuUsed;
    }

    public function setSkuUsed(string $skuUsed): self
    {
        $this->skuUsed = $skuUsed;

        return $this;
    }

    public function getSkuErp(): ?string
    {
        return $this->skuErp;
    }

    public function setSkuErp(string $skuErp): self
    {
        $this->skuErp = $skuErp;

        return $this;
    }
}
