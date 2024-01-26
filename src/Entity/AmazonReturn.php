<?php

namespace App\Entity;

use App\Entity\Product;
use App\Helper\Traits\TraitTimeUpdated;
use App\Helper\Utils\DatetimeUtils;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class AmazonReturn implements \Stringable
{
    use TraitTimeUpdated;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    #[Groups(['export_order'])]
    private ?\DateTimeInterface $returnDate = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    #[Groups(['export_order'])]
    private ?string $orderId = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    #[Groups(['export_order'])]
    private ?string $sku = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    #[Groups(['export_order'])]
    private ?string $asin = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    #[Groups(['export_order'])]
    private ?string $fnsku = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[Groups(['export_order'])]
    private ?int $quantity = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    #[Groups(['export_order'])]
    private ?string $fulfillmentCenterId = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    #[Groups(['export_order'])]
    private ?string $detailedDisposition = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    #[Groups(['export_order'])]
    private ?string $reason = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    #[Groups(['export_order'])]
    private ?string $status = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    #[Groups(['export_order'])]
    private ?string $licensePlateNumber = null;


    #[ORM\ManyToOne(targetEntity: Product::class)]
    private ?\App\Entity\Product $product = null;

    #[ORM\ManyToOne(targetEntity: AmazonRemovalOrder::class, inversedBy: 'returns')]
    private ?\App\Entity\AmazonRemovalOrder $amazonRemovalOrder = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    #[Groups(['export_order'])]
    private ?string $marketplaceName = null;

    #[Groups(['export_order'])]
    public function getAmazonOrderIdProductId()
    {
        return $this->product ? $this->orderId . '_' . $this->product->getId() :  $this->orderId . '_';
    }


    public function __toString(): string
    {
        return "#".$this->orderId.' Sku>'.$this->sku.' LPN> '.$this->licensePlateNumber;
    }

    #[Groups(['export_order'])]
    public function getProductId()
    {
        return $this->product ? $this->product->getId() :  null;
    }


    #[Groups(['export_order'])]
    public function getReturnDateFormatYmd()
    {
        return $this->returnDate->format('Y-m-d');
    }


    #[Groups(['export_order'])]
    public function getReturnDateFormatCalendar()
    {
        return $this->returnDate->format('j/n/Y');
    }


    public function importData(array $reimbursementAmz)
    {
        foreach ($reimbursementAmz as $key => $value) {
            $attribute = $this->checkIfImportAttribute($key);
            if ($attribute) {
                if (in_array($key, ["return-date"])) {
                    $this->{$attribute} = DatetimeUtils::transformFromIso8601($value);
                } else {
                    $this->{$attribute} = strlen((string) $value) > 0 ? $value : null;
                }
            }
        }
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

    public function getReturnDate(): ?\DateTimeInterface
    {
        return $this->returnDate;
    }

    public function setReturnDate(\DateTimeInterface $returnDate): self
    {
        $this->returnDate = $returnDate;

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

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(string $sku): self
    {
        $this->sku = $sku;

        return $this;
    }

    public function getAsin(): ?string
    {
        return $this->asin;
    }

    public function setAsin(string $asin): self
    {
        $this->asin = $asin;

        return $this;
    }

    public function getFnsku(): ?string
    {
        return $this->fnsku;
    }

    public function setFnsku(string $fnsku): self
    {
        $this->fnsku = $fnsku;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getFulfillmentCenterId(): ?string
    {
        return $this->fulfillmentCenterId;
    }

    public function setFulfillmentCenterId(?string $fulfillmentCenterId): self
    {
        $this->fulfillmentCenterId = $fulfillmentCenterId;

        return $this;
    }

    public function getDetailedDisposition(): ?string
    {
        return $this->detailedDisposition;
    }

    public function setDetailedDisposition(?string $detailedDisposition): self
    {
        $this->detailedDisposition = $detailedDisposition;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

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

    public function getLicensePlateNumber(): ?string
    {
        return $this->licensePlateNumber;
    }

    public function setLicensePlateNumber(?string $licensePlateNumber): self
    {
        $this->licensePlateNumber = $licensePlateNumber;

        return $this;
    }

    public function getAmazonRemovalOrder(): ?AmazonRemovalOrder
    {
        return $this->amazonRemovalOrder;
    }

    public function setAmazonRemovalOrder(?AmazonRemovalOrder $amazonRemovalOrder): self
    {
        $this->amazonRemovalOrder = $amazonRemovalOrder;

        return $this;
    }

    public function getMarketplaceName(): ?string
    {
        return $this->marketplaceName;
    }

    public function setMarketplaceName(?string $marketplaceName): self
    {
        $this->marketplaceName = $marketplaceName;

        return $this;
    }
}
