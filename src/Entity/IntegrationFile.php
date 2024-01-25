<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class IntegrationFile
{

    final public const TYPE_INVOICE = 1;
    final public const TYPE_CREDIT = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $documentNumber = null;

    #[ORM\Column(type: 'integer')]
    private ?int $documentType = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $externalOrderId = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $profileChannel = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $currency = null;

    #[ORM\Column(type: 'float')]
    private ?float $totalAmount = null;

    #[ORM\Column(type: 'float')]
    private ?float $totalVat = null;

    #[ORM\Column(type: 'float')]
    private ?float $totalVatIncluded = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateUpdated = null;

    #[ORM\Column(type: 'integer')]
    private ?int $channelOrderId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $channelAdjustementId = null;


    public function __construct($data = [])
    {
        if (count($data) > 0) {
            $this->documentNumber = $data['document_no'];
            $this->externalOrderId = $data['external_order_id'];
            $this->profileChannel = $data['ca_marketplace_id'];
            $this->currency = $data['currency'];
            $this->totalAmount = $this->convertFloat($data['total_amount']);
            $this->totalVatIncluded = $this->convertFloat($data['total_incVat']);
            $this->totalVat = $this->convertFloat($data['vat_amount']);
            $this->documentType = $data['document_type'] == 'invoice' ? self::TYPE_INVOICE : self::TYPE_CREDIT;
        }
        $this->dateUpdated = new \DateTime();
    }

    /**
     * Return the path to store tje uploaded invoices
     *
     * @return string
     */
    public function getNewFileDestination(): string
    {

        $path = "integrated/" . $this->getProfileChannel() . "/";
        $path .= $this->documentType == self::TYPE_INVOICE ? 'invoices' : 'credit_notes';
        $path .= "/" . $this->externalOrderId . '_' . str_replace("/", "-", (string) $this->documentNumber) . '_' . date('Ymd-His') . '.pdf';
        return $path;
    }

    private function convertFloat($stringFloat)
    {
        return floatval(str_replace(",", '.', (string) $stringFloat));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocumentNumber(): ?string
    {
        return $this->documentNumber;
    }

    public function setDocumentNumber(string $documentNumber): self
    {
        $this->documentNumber = $documentNumber;

        return $this;
    }

    public function getDocumentType(): ?int
    {
        return $this->documentType;
    }

    public function setDocumentType(int $documentType): self
    {
        $this->documentType = $documentType;

        return $this;
    }

    public function getExternalOrderId(): ?string
    {
        return $this->externalOrderId;
    }

    public function setExternalOrderId(string $externalOrderId): self
    {
        $this->externalOrderId = $externalOrderId;

        return $this;
    }

    public function getProfileChannel(): ?string
    {
        return $this->profileChannel;
    }

    public function setProfileChannel(string $profileChannel): self
    {
        $this->profileChannel = $profileChannel;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getTotalAmount(): ?float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(float $totalAmount): self
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getTotalVat(): ?float
    {
        return $this->totalVat;
    }

    public function setTotalVat(float $totalVat): self
    {
        $this->totalVat = $totalVat;

        return $this;
    }

    public function getTotalVatIncluded(): ?float
    {
        return $this->totalVatIncluded;
    }

    public function setTotalVatIncluded(float $totalVatIncluded): self
    {
        $this->totalVatIncluded = $totalVatIncluded;

        return $this;
    }

    public function getDateUpdated(): ?\DateTimeInterface
    {
        return $this->dateUpdated;
    }

    public function setDateUpdated(\DateTimeInterface $dateUpdated): self
    {
        $this->dateUpdated = $dateUpdated;

        return $this;
    }

    public function getChannelOrderId(): ?int
    {
        return $this->channelOrderId;
    }

    public function setChannelOrderId(int $channelOrderId): self
    {
        $this->channelOrderId = $channelOrderId;

        return $this;
    }

    public function getChannelAdjustementId(): ?int
    {
        return $this->channelAdjustementId;
    }

    public function setChannelAdjustementId(?int $channelAdjustementId): self
    {
        $this->channelAdjustementId = $channelAdjustementId;

        return $this;
    }
}
