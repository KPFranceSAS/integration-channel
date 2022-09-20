<?php

namespace App\Entity;

use App\Entity\ProductSaleChannel;
use App\Helper\Traits\TraitTimeUpdated;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 *
 */
class ProductLogEntry extends AbstractLogEntry
{
    /**
     * @var int
     *
     * @ORM\Column(name="product_id", type="integer", nullable=true)
     */
    private $productId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $productSku;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $saleChannelId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $saleChannelName;


    public function getHumanType(){
        switch ($this->getObjectClass()){
            case Promotion::class :
                return 'Promotion';
            case ProductSaleChannel::class :
                return 'Product on sale channel';

        }
        return $this->getObjectClass();
    }

    /**
     * @return int
     */
    public function getDivisionId(): int
    {
        return $this->productId;
    }

    /**
     * @param string $productId
     */
    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getProductSku(): ?string
    {
        return $this->productSku;
    }

    public function setProductSku(?string $productSku): self
    {
        $this->productSku = $productSku;

        return $this;
    }

    public function getSaleChannelId(): ?int
    {
        return $this->saleChannelId;
    }

    public function setSaleChannelId(?int $saleChannelId): self
    {
        $this->saleChannelId = $saleChannelId;

        return $this;
    }

    public function getSaleChannelName(): ?string
    {
        return $this->saleChannelName;
    }

    public function setSaleChannelName(?string $saleChannelName): self
    {
        $this->saleChannelName = $saleChannelName;

        return $this;
    }
}
