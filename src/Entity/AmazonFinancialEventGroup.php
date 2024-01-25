<?php

namespace App\Entity;

use App\Entity\AmazonFinancialEvent;
use App\Helper\Traits\TraitTimeUpdated;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class AmazonFinancialEventGroup
{
    use TraitTimeUpdated;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['export_order'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['export_order'])]
    private ?string $financialEventId = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['export_order'])]
    private ?string $processingStatus = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['export_order'])]
    private ?string $fundTransfertStatus = null;


    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['export_order'])]
    private ?\DateTimeInterface $fundTransferDate = null;




    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['export_order'])]
    private ?string $traceIdentfier = null;



    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['export_order'])]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['export_order'])]
    private ?\DateTimeInterface $endDate = null;



    #[ORM\Column(type: 'float', nullable: true)]
    #[Groups(['export_order'])]
    private ?float $originalTotal = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Groups(['export_order'])]
    private ?float $convertedTotal = null;


    #[ORM\Column(type: 'float', nullable: true)]
    #[Groups(['export_order'])]
    private ?float $originalTotalCurrency = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Groups(['export_order'])]
    private ?float $convertedTotalCurrency = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Groups(['export_order'])]
    private ?float $beginningBalance = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Groups(['export_order'])]
    private ?float $beginningBalanceCurrency = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['export_order'])]
    private ?string $currencyCode = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\AmazonFinancialEvent>
     */
    #[ORM\OneToMany(targetEntity: AmazonFinancialEvent::class, mappedBy: 'eventGroup', orphanRemoval: true)]
    private \Doctrine\Common\Collections\Collection $amazonFinancialEvents;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['export_order'])]
    private ?string $marketplace = null;



    #[Groups(['export_order'])]
    public function getStartDateFormatYmd()
    {
        return $this->startDate ? $this->startDate->format('Y-m-d') :  null;
    }


    #[Groups(['export_order'])]
    public function getStartDateFormatCalendar()
    {
        return $this->startDate ? $this->startDate->format('j/n/Y') :  null;
    }



    #[Groups(['export_order'])]
    public function getEndDateFormatYmd()
    {
        return $this->endDate ? $this->endDate->format('Y-m-d') :  null;
    }


    #[Groups(['export_order'])]
    public function getEndDateFormatCalendar()
    {
        return $this->endDate ? $this->endDate->format('j/n/Y') :  null;
    }

    public function __construct()
    {
        $this->amazonFinancialEvents = new ArrayCollection();
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

    public function getFundTransferDate(): ?DateTimeInterface
    {
        return $this->fundTransferDate;
    }

    public function setFundTransferDate(?DateTimeInterface $fundTransferDate): self
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

    public function getStartDate(): ?DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

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

    public function getAmazonFinancialEvents(): \Doctrine\Common\Collections\Collection
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

    public function getMarketplace(): ?string
    {
        return $this->marketplace;
    }

    public function setMarketplace(?string $marketplace): self
    {
        $this->marketplace = $marketplace;

        return $this;
    }
}
