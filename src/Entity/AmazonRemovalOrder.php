<?php

namespace App\Entity;

use App\Entity\AmazonRemoval;
use App\Helper\Traits\TraitTimeUpdated;
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
    use TraitTimeUpdated;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="datetime")
     *  @Groups({"export_order"})
     */
    private ?\DateTimeInterface $requestDate = null;

    /**
     * @ORM\Column(type="string", length=255)
     *  @Groups({"export_order"})
     */
    private ?string $orderId = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private ?string $orderType = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private ?string $serviceSpeed = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private ?string $orderStatus = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *  @Groups({"export_order"})
     */
    private ?\DateTimeInterface $lastUpdatedDate = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private ?string $sku = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private ?string $fnsku = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *  @Groups({"export_order"})
     */
    private ?string $disposition = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *  @Groups({"export_order"})
     */
    private ?int $requestedQuantity = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *  @Groups({"export_order"})
     */
    private ?int $cancelledQuantity = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *  @Groups({"export_order"})
     */
    private ?int $disposedQuantity = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *  @Groups({"export_order"})
     */
    private ?int $shippedQuantity = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *  @Groups({"export_order"})
     */
    private ?int $inProcessQuantity = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     *  @Groups({"export_order"})
     */
    private ?float $removalFee = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private ?float $removalFeeCurrency = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
    *  @Groups({"export_order"})
     */
    private ?string $currency = null;


    /**
     * @ORM\ManyToOne(targetEntity=Product::class)
     */
    private ?\App\Entity\Product $product = null;

    /**
     * @ORM\OneToMany(targetEntity=AmazonReturn::class, mappedBy="amazonRemovalOrder")
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\AmazonReturn>
     */
    private \Doctrine\Common\Collections\Collection $returns;

    /**
     * @ORM\OneToMany(targetEntity=FbaReturn::class, mappedBy="amazonRemoval")
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\FbaReturn>
     */
    private \Doctrine\Common\Collections\Collection $fbaReturns;

    /**
     * @ORM\ManyToOne(targetEntity=AmazonRemoval::class, inversedBy="amazonRemovalOrders")
     */
    private ?\App\Entity\AmazonRemoval $amazonRemoval = null;


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


    public function calculateStatus() : string
    {
        $quantity = $this->requestedQuantity - $this->cancelledQuantity;
        if ($quantity == 0) {
            return AmazonRemoval::CANCELLED;
        }
        if ($this->orderType == 'Return') {
            return ($quantity - $this->shippedQuantity) ==0 ? AmazonRemoval::COMPLETED : AmazonRemoval::PENDING;
        } else {
            return ($quantity - $this->disposedQuantity) ==0 ? AmazonRemoval::COMPLETED : AmazonRemoval::PENDING;
        }
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
                    $this->{$attribute} =  strlen((string) $value) > 0 ? $value : null;
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
        return lcfirst(str_replace($separator, '', ucwords((string) $input, $separator)));
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

   
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }


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


    public function getFbaReturns(): \Doctrine\Common\Collections\Collection
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

    public function getAmazonRemoval(): ?AmazonRemoval
    {
        return $this->amazonRemoval;
    }

    public function setAmazonRemoval(?AmazonRemoval $amazonRemoval): self
    {
        $this->amazonRemoval = $amazonRemoval;

        return $this;
    }
}
