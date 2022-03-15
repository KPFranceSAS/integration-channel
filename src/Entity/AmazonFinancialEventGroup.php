<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * 
 */
class AmazonFinancialEventGroup
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $financialEventId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $processingStatus;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fundTransfertStatus;


    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $fundTransferDate;




    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $traceIdentfier;



    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $startDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $endDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;


    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $originalTotal;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $convertedTotal;


    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $originalTotalCurrency;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $convertedTotalCurrency;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $beginningBalance;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $beginningBalanceCurrency;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $currencyCode;

    /**
     * @ORM\OneToMany(targetEntity=AmazonFinancialEvent::class, mappedBy="eventGroup", orphanRemoval=true)
     */
    private $amazonFinancialEvents;

    public function __construct()
    {
        $this->amazonFinancialEvents = new ArrayCollection();
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

    public function getFinancialEventId(): ?string
    {
        return $this->financialEventId;
    }

    public function setFinancialEventId(string $financialEventId): self
    {
        $this->financialEventId = $financialEventId;

        return $this;
    }

    public function getProcessingStatus(): ?string
    {
        return $this->processingStatus;
    }

    public function setProcessingStatus(string $processingStatus): self
    {
        $this->processingStatus = $processingStatus;

        return $this;
    }

    public function getFundTransfertStatus(): ?string
    {
        return $this->fundTransfertStatus;
    }

    public function setFundTransfertStatus(?string $fundTransfertStatus): self
    {
        $this->fundTransfertStatus = $fundTransfertStatus;

        return $this;
    }

    public function getFundTransferDate(): ?\DateTimeInterface
    {
        return $this->fundTransferDate;
    }

    public function setFundTransferDate(?\DateTimeInterface $fundTransferDate): self
    {
        $this->fundTransferDate = $fundTransferDate;

        return $this;
    }

    public function getTraceIdentfier(): ?string
    {
        return $this->traceIdentfier;
    }

    public function setTraceIdentfier(?string $traceIdentfier): self
    {
        $this->traceIdentfier = $traceIdentfier;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getOriginalTotal(): ?float
    {
        return $this->originalTotal;
    }

    public function setOriginalTotal(?float $originalTotal): self
    {
        $this->originalTotal = $originalTotal;

        return $this;
    }

    public function getConvertedTotal(): ?float
    {
        return $this->convertedTotal;
    }

    public function setConvertedTotal(?float $convertedTotal): self
    {
        $this->convertedTotal = $convertedTotal;

        return $this;
    }

    public function getOriginalTotalCurrency(): ?float
    {
        return $this->originalTotalCurrency;
    }

    public function setOriginalTotalCurrency(?float $originalTotalCurrency): self
    {
        $this->originalTotalCurrency = $originalTotalCurrency;

        return $this;
    }

    public function getConvertedTotalCurrency(): ?float
    {
        return $this->convertedTotalCurrency;
    }

    public function setConvertedTotalCurrency(?float $convertedTotalCurrency): self
    {
        $this->convertedTotalCurrency = $convertedTotalCurrency;

        return $this;
    }

    public function getBeginningBalance(): ?float
    {
        return $this->beginningBalance;
    }

    public function setBeginningBalance(?float $beginningBalance): self
    {
        $this->beginningBalance = $beginningBalance;

        return $this;
    }

    public function getBeginningBalanceCurrency(): ?float
    {
        return $this->beginningBalanceCurrency;
    }

    public function setBeginningBalanceCurrency(?float $beginningBalanceCurrency): self
    {
        $this->beginningBalanceCurrency = $beginningBalanceCurrency;

        return $this;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(?string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    /**
     * @return Collection|AmazonFinancialEvent[]
     */
    public function getAmazonFinancialEvents(): Collection
    {
        return $this->amazonFinancialEvents;
    }

    public function addAmazonFinancialEvent(AmazonFinancialEvent $amazonFinancialEvent): self
    {
        if (!$this->amazonFinancialEvents->contains($amazonFinancialEvent)) {
            $this->amazonFinancialEvents[] = $amazonFinancialEvent;
            $amazonFinancialEvent->setEventGroup($this);
        }

        return $this;
    }

    public function removeAmazonFinancialEvent(AmazonFinancialEvent $amazonFinancialEvent): self
    {
        if ($this->amazonFinancialEvents->removeElement($amazonFinancialEvent)) {
            // set the owning side to null (unless already changed)
            if ($amazonFinancialEvent->getEventGroup() === $this) {
                $amazonFinancialEvent->setEventGroup(null);
            }
        }

        return $this;
    }
}
