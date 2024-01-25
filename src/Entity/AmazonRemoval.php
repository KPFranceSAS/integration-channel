<?php

namespace App\Entity;

use App\Entity\AmazonRemovalOrder;
use App\Helper\Traits\TraitLoggable;
use App\Helper\Traits\TraitTimeUpdated;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class AmazonRemoval
{
    use TraitTimeUpdated;

    use TraitLoggable;

    final public const CREATED ='Created';

    final public const COMPLETED ='Completed';

    final public const PENDING ='Pending';

    final public const CANCELLED ='Cancelled';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $orderId = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $status = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $orderType = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private ?\DateTimeInterface $requestDate = null;

    /**
     * @ORM\OneToMany(targetEntity=AmazonRemovalOrder::class, mappedBy="amazonRemoval")
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\AmazonRemovalOrder>
     */
    private \Doctrine\Common\Collections\Collection $amazonRemovalOrders;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $shipCity = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $shipPostalCode = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $shipState = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $shipCountry = null;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $notifyedCreation = null;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $notifyedEnd = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $amazonOrderId = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $lastUpdateDate = null;

    public function __construct()
    {
        $this->amazonRemovalOrders = new ArrayCollection();
    }

    public function updateStatus()
    {
        $lastUpdate = null;
        foreach ($this->amazonRemovalOrders as $am) {
            $this->setStatus($am->getOrderStatus());
            if (!$lastUpdate || $am->getLastUpdatedDate() > $lastUpdate) {
                $lastUpdate = $am->getLastUpdatedDate();
            }
        }
        $this->lastUpdateDate = $lastUpdate;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getOrderType(): ?string
    {
        return $this->orderType;
    }

    public function setOrderType(string $orderType): self
    {
        $this->orderType = $orderType;

        return $this;
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

    /**
     * @return Collection<int, AmazonRemovalOrder>
     */
    public function getAmazonRemovalOrders(): Collection
    {
        return $this->amazonRemovalOrders;
    }

    public function addAmazonRemovalOrder(AmazonRemovalOrder $amazonRemovalOrder): self
    {
        if (!$this->amazonRemovalOrders->contains($amazonRemovalOrder)) {
            $this->amazonRemovalOrders[] = $amazonRemovalOrder;
            $amazonRemovalOrder->setAmazonRemoval($this);
        }

        return $this;
    }

    public function removeAmazonRemovalOrder(AmazonRemovalOrder $amazonRemovalOrder): self
    {
        if ($this->amazonRemovalOrders->removeElement($amazonRemovalOrder)) {
            // set the owning side to null (unless already changed)
            if ($amazonRemovalOrder->getAmazonRemoval() === $this) {
                $amazonRemovalOrder->setAmazonRemoval(null);
            }
        }

        return $this;
    }

    public function getShipCity(): ?string
    {
        return $this->shipCity;
    }

    public function setShipCity(?string $shipCity): self
    {
        $this->shipCity = $shipCity;

        return $this;
    }

    public function getShipPostalCode(): ?string
    {
        return $this->shipPostalCode;
    }

    public function setShipPostalCode(?string $shipPostalCode): self
    {
        $this->shipPostalCode = $shipPostalCode;

        return $this;
    }

    public function getShipState(): ?string
    {
        return $this->shipState;
    }

    public function setShipState(?string $shipState): self
    {
        $this->shipState = $shipState;

        return $this;
    }

    public function getShipCountry(): ?string
    {
        return $this->shipCountry;
    }

    public function setShipCountry(?string $shipCountry): self
    {
        $this->shipCountry = $shipCountry;

        return $this;
    }

    public function isNotifyedCreation(): ?bool
    {
        return $this->notifyedCreation;
    }

    public function setNotifyedCreation(bool $notifyedCreation): self
    {
        $this->notifyedCreation = $notifyedCreation;

        return $this;
    }

    public function isNotifyedEnd(): ?bool
    {
        return $this->notifyedEnd;
    }

    public function setNotifyedEnd(bool $notifyedEnd): self
    {
        $this->notifyedEnd = $notifyedEnd;

        return $this;
    }

    public function getAmazonOrderId(): ?string
    {
        return $this->amazonOrderId;
    }

    public function setAmazonOrderId(?string $amazonOrderId): self
    {
        $this->amazonOrderId = $amazonOrderId;

        return $this;
    }

    public function getLastUpdateDate(): ?\DateTimeInterface
    {
        return $this->lastUpdateDate;
    }

    public function setLastUpdateDate(?\DateTimeInterface $lastUpdateDate): self
    {
        $this->lastUpdateDate = $lastUpdateDate;

        return $this;
    }
}
