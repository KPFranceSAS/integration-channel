<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class IntegrationFile
{

    const TYPE_INVOICE = 1;
    const TYPE_CREDIT = 2;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $documentNumber;

    /**
     * @ORM\Column(type="integer")
     */
    private $documentType;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $externalOrderId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $profileChannel;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $currency;

    /**
     * @ORM\Column(type="float")
     */
    private $totalAmount;

    /**
     * @ORM\Column(type="float")
     */
    private $totalVat;

    /**
     * @ORM\Column(type="float")
     */
    private $totalVatIncluded;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateUpdated;

    /**
     * @ORM\Column(type="integer")
     */
    private $channelOrderId;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $channelAdjustementId;


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
        $path .= "/" . $this->externalOrderId . '_' . str_replace("/", "-", $this->documentNumber) . '_' . date('Ymd-His') . '.pdf';
        return $path;
    }

    private function convertFloat($stringFloat)
    {
        return floatval(str_replace(",", '.', $stringFloat));
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
