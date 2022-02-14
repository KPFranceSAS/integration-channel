<?php

namespace App\Entity;

use App\Entity\Product;
use App\Helper\Utils\DatetimeUtils;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class AmazonReturn
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"export_order"})
     */
    private $returnDate;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"export_order"})
     */
    private $orderId;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"export_order"})
     */
    private $sku;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"export_order"})
     */
    private $asin;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"export_order"})
     */
    private $fnsku;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"export_order"})
     */
    private $quantity;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private $fulfillmentCenterId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private $detailedDisposition;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private $reason;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private $licensePlateNumber;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $product;

    /**
     *  @Groups({"export_order"})
     */
    public function getProductId()
    {
        return $this->product ? $this->product->getId() :  null;
    }


    public function importData(array $reimbursementAmz)
    {
        foreach ($reimbursementAmz as $key => $value) {
            $attribute = $this->checkIfImportAttribute($key);
            if ($attribute) {
                if (in_array($key, ["return-date"])) {
                    $this->{$attribute} = DatetimeUtils::transformFromIso8601($value);
                } else {
                    $this->{$attribute} = strlen($value) > 0 ? $value : null;
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

    public function getCustomerComments(): ?string
    {
        return $this->customerComments;
    }

    public function setCustomerComments(?string $customerComments): self
    {
        $this->customerComments = $customerComments;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
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

    public function getLicensePlateNumber(): ?string
    {
        return $this->licensePlateNumber;
    }

    public function setLicensePlateNumber(?string $licensePlateNumber): self
    {
        $this->licensePlateNumber = $licensePlateNumber;

        return $this;
    }
}
