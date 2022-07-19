<?php

namespace App\Entity;

use App\Helper\Utils\DatetimeUtils;
use App\Helper\Utils\ExchangeRateCalculator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class AmazonRemovalOrder
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     *  @Groups({"export_order"})
     */
    private $requestDate;

    /**
     * @ORM\Column(type="string", length=255)
     *  @Groups({"export_order"})
     */
    private $orderId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private $orderType;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private $serviceSpeed;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private $orderStatus;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *  @Groups({"export_order"})
     */
    private $lastUpdatedDate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private $sku;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private $fnsku;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private $disposition;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *  @Groups({"export_order"})
     */
    private $requestedQuantity;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *  @Groups({"export_order"})
     */
    private $cancelledQuantity;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *  @Groups({"export_order"})
     */
    private $disposedQuantity;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *  @Groups({"export_order"})
     */
    private $shippedQuantity;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *  @Groups({"export_order"})
     */
    private $inProcessQuantity;

    /**
     * @ORM\Column(type="float", nullable=true)
     *  @Groups({"export_order"})
     */
    private $removalFee;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $removalFeeCurrency;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
    *  @Groups({"export_order"})
     */
    private $currency;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class)
     */
    private $product;

    /**
     * @ORM\OneToMany(targetEntity=AmazonReturn::class, mappedBy="amazonRemovalOrder")
     */
    private $returns;

    /**
     * @ORM\OneToMany(targetEntity=FbaReturn::class, mappedBy="amazonRemoval")
     */
    private $fbaReturns;


    /**
    *  @Groups({"export_order"})
    */
    public function getRequestDateFormatYmd()
    {
        return $this->requestDate->format('Y-m-d');
    }


    /**
    *  @Groups({"export_order"})
    */
    public function getRequestDateFormatCalendar()
    {
        return $this->requestDate->format('j/n/Y');
    }

   

    public function __construct()
    {
        $this->returns = new ArrayCollection();
        $this->fbaReturns = new ArrayCollection();
    }


    public function importData(ExchangeRateCalculator $calculator, array $orderAmz)
    {
        foreach ($orderAmz as $key => $value) {
            $attribute = $this->checkIfImportAttribute($key);
            if ($attribute) {
                if (in_array($key, ["request-date", "last-updated-date"])) {
                    $this->{$attribute} = DatetimeUtils::transformFromIso8601($value);
                } elseif (in_array($key, [
                    "removal-fee"
                ])) {
                    $valueFormate = round(floatval($value), 2);
                    $this->{$attribute . 'Currency'} = $valueFormate > 0 ? $valueFormate : null;
                    $this->{$attribute} =  $valueFormate > 0 ? round($calculator->getConvertedAmountDate($valueFormate, $orderAmz['currency'], $this->requestDate), 2) : null;
                } else {
                    $this->{$attribute} =  strlen($value) > 0 ? $value : null;
                }
            }
        }
    }

    /**
     *  @Groups({"export_order"})
     */
    public function getProductId()
    {
        return $this->product ? $this->product->getId() :  null;
    }


    private function checkIfImportAttribute($key)
    {
        $attribute = $this->camelize($key);
        return property_exists($this, $attribute) ? $attribute : null;
    }


    private function camelize($input, $separator = '-')
    {
        return lcfirst(str_replace($separator, '', ucwords($input, $separator)));
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

    public function getRequestDate(): ?\DateTimeInterface
    {
        return $this->requestDate;
    }

    public function setRequestDate(\DateTimeInterface $requestDate): self
    {
        $this->requestDate = $requestDate;

        return $this;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getOrderType(): ?string
    {
        return $this->orderType;
    }

    public function setOrderType(?string $orderType): self
    {
        $this->orderType = $orderType;

        return $this;
    }

    public function getServiceSpeed(): ?string
    {
        return $this->serviceSpeed;
    }

    public function setServiceSpeed(?string $serviceSpeed): self
    {
        $this->serviceSpeed = $serviceSpeed;

        return $this;
    }

    public function getOrderStatus(): ?string
    {
        return $this->orderStatus;
    }

    public function setOrderStatus(?string $orderStatus): self
    {
        $this->orderStatus = $orderStatus;

        return $this;
    }

    public function getLastUpdatedDate(): ?\DateTimeInterface
    {
        return $this->lastUpdatedDate;
    }

    public function setLastUpdatedDate(?\DateTimeInterface $lastUpdatedDate): self
    {
        $this->lastUpdatedDate = $lastUpdatedDate;

        return $this;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): self
    {
        $this->sku = $sku;

        return $this;
    }



    public function getAsin(): ?string
    {
        return $this->fnsku;
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

    public function getDisposition(): ?string
    {
        return $this->disposition;
    }

    public function setDisposition(?string $disposition): self
    {
        $this->disposition = $disposition;

        return $this;
    }

    public function getRequestedQuantity(): ?int
    {
        return $this->requestedQuantity;
    }

    public function setRequestedQuantity(?int $requestedQuantity): self
    {
        $this->requestedQuantity = $requestedQuantity;

        return $this;
    }

    public function getCancelledQuantity(): ?int
    {
        return $this->cancelledQuantity;
    }

    public function setCancelledQuantity(?int $cancelledQuantity): self
    {
        $this->cancelledQuantity = $cancelledQuantity;

        return $this;
    }

    public function getDisposedQuantity(): ?int
    {
        return $this->disposedQuantity;
    }

    public function setDisposedQuantity(?int $disposedQuantity): self
    {
        $this->disposedQuantity = $disposedQuantity;

        return $this;
    }

    public function getShippedQuantity(): ?int
    {
        return $this->shippedQuantity;
    }

    public function setShippedQuantity(?int $shippedQuantity): self
    {
        $this->shippedQuantity = $shippedQuantity;

        return $this;
    }

    public function getInProcessQuantity(): ?int
    {
        return $this->inProcessQuantity;
    }

    public function setInProcessQuantity(?int $inProcessQuantity): self
    {
        $this->inProcessQuantity = $inProcessQuantity;

        return $this;
    }

    public function getRemovalFee(): ?float
    {
        return $this->removalFee;
    }

    public function setRemovalFee(?float $removalFee): self
    {
        $this->removalFee = $removalFee;

        return $this;
    }

    public function getRemovalFeeCurrency(): ?float
    {
        return $this->removalFeeCurrency;
    }

    public function setRemovalFeeCurrency(?float $removalFeeCurrency): self
    {
        $this->removalFeeCurrency = $removalFeeCurrency;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return Collection|AmazonReturn[]
     */
    public function getReturns(): Collection
    {
        return $this->returns;
    }

    public function addReturn(AmazonReturn $return): self
    {
        if (!$this->returns->contains($return)) {
            $this->returns[] = $return;
            $return->setAmazonRemovalOrder($this);
        }

        return $this;
    }

    public function removeReturn(AmazonReturn $return): self
    {
        if ($this->returns->removeElement($return)) {
            // set the owning side to null (unless already changed)
            if ($return->getAmazonRemovalOrder() === $this) {
                $return->setAmazonRemovalOrder(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|FbaReturn[]
     */
    public function getFbaReturns(): Collection
    {
        return $this->fbaReturns;
    }

    public function addFbaReturn(FbaReturn $fbaReturn): self
    {
        if (!$this->fbaReturns->contains($fbaReturn)) {
            $this->fbaReturns[] = $fbaReturn;
            $fbaReturn->setAmazonRemoval($this);
        }

        return $this;
    }

    public function removeFbaReturn(FbaReturn $fbaReturn): self
    {
        if ($this->fbaReturns->removeElement($fbaReturn)) {
            // set the owning side to null (unless already changed)
            if ($fbaReturn->getAmazonRemoval() === $this) {
                $fbaReturn->setAmazonRemoval(null);
            }
        }

        return $this;
    }
}
