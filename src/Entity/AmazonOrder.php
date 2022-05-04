<?php

namespace App\Entity;

use App\Helper\Utils\DatetimeUtils;
use App\Helper\Utils\ExchangeRateCalculator;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class AmazonOrder
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"export_order"})
     */
    private $amazonOrderId;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"export_order"})
     */
    private $merchantOrderId;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"export_order"})
     */
    private $purchaseDate;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"export_order"})
     */
    private $lastUpdatedDate;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"export_order"})
     */
    private $orderStatus;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private $fulfillmentChannel;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"export_order"})
     */
    private $salesChannel;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private $shipServiceLevel;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $sku;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $asin;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private $itemStatus;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"export_order"})
     */
    private $quantity;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private $currency;



    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private $shipCity;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private $shipPostalCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private $shipState;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private $shipCountry;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private $promotionIds;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fulfilledBy;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"export_order"})
     */
    private $itemPrice;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"export_order"})
     */
    private $itemTax;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"export_order"})
     */
    private $shippingPrice;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"export_order"})
     */
    private $shippingTax;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"export_order"})
     */
    private $giftWrapPrice;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"export_order"})
     */
    private $giftWrapTax;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"export_order"})
     */
    private $itemPromotionDiscount;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"export_order"})
     */
    private $shipPromotionDiscount;


    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"export_order"})
     */
    private $vatExclusiveItemPrice;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"export_order"})
     */
    private $vatExclusiveShippingPrice;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"export_order"})
     */
    private $vatExclusiveGiftwrapPrice;


    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"export_order"})
     */
    private $itemPriceCurrency;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"export_order"})
     */
    private $itemTaxCurrency;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"export_order"})
     */
    private $shippingPriceCurrency;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"export_order"})
     */
    private $shippingTaxCurrency;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"export_order"})
     */
    private $giftWrapPriceCurrency;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $giftWrapTaxCurrency;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $itemPromotionDiscountCurrency;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $shipPromotionDiscountCurrency;


    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $vatExclusiveItemPriceCurrency;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $vatExclusiveShippingPriceCurrency;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $vatExclusiveGiftwrapPriceCurrency;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class)
     */
    private $product;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"export_order"})
     */
    private $integrated = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"export_order"})
     */
    private $integrationNumber;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isMultiline = false;


    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isReturn = false;



    public function importData(ExchangeRateCalculator $calculator, array $orderAmz)
    {
        foreach ($orderAmz as $key => $value) {
            $attribute = $this->checkIfImportAttribute($key);
            if ($attribute) {
                if (in_array($key, ["purchase-date", "last-updated-date"])) {
                    $this->{$attribute} = DatetimeUtils::transformFromIso8601($value);
                } elseif (in_array($key, [
                    "item-price",
                    "item-tax",
                    "shipping-price",
                    "shipping-tax",
                    "gift-wrap-price",
                    "gift-wrap-tax",
                    "item-promotion-discount",
                    "ship-promotion-discount",
                    "vat-exclusive-item-price",
                    "vat-exclusive-shipping-price",
                    "vat-exclusive-giftwrap-price",
                ])) {
                    $valueFormate = round(floatval($value), 2);
                    $this->{$attribute . 'Currency'} = $valueFormate > 0 ? $valueFormate : null;
                    $this->{$attribute} =  $valueFormate > 0 ? round($calculator->getConvertedAmountDate($valueFormate, $this->currency, $this->purchaseDate), 2) : null;
                } else {
                    $this->{$attribute} =  strlen($value) > 0 ? $value : null;
                }
            }
        }
        if ($this->merchantOrderId != $this->amazonOrderId) {
            $this->isReturn = true;
        }
    }

    /**
     *  @Groups({"export_order"})
     */
    public function getProductId()
    {
        return $this->product ? $this->product->getId() :  null;
    }

    /**
     *  @Groups({"export_order"})
     */
    public function getAmazonOrderIdProductId()
    {
        return $this->product ? $this->amazonOrderId . '_' . $this->product->getId() :  $this->amazonOrderId . '_';
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

    public function getAmazonOrderId(): ?string
    {
        return $this->amazonOrderId;
    }

    public function setAmazonOrderId(string $amazonOrderId): self
    {
        $this->amazonOrderId = $amazonOrderId;

        return $this;
    }

    public function getMerchantOrderId(): ?string
    {
        return $this->merchantOrderId;
    }

    public function setMerchantOrderId(string $merchantOrderId): self
    {
        $this->merchantOrderId = $merchantOrderId;

        return $this;
    }

    public function getPurchaseDate(): ?\DateTimeInterface
    {
        return $this->purchaseDate;
    }

    public function setPurchaseDate(\DateTimeInterface $purchaseDate): self
    {
        $this->purchaseDate = $purchaseDate;

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

    public function getLastUpdatedDate(): ?\DateTimeInterface
    {
        return $this->lastUpdatedDate;
    }

    public function setLastUpdatedDate(\DateTimeInterface $lastUpdatedDate): self
    {
        $this->lastUpdatedDate = $lastUpdatedDate;

        return $this;
    }

    public function getOrderStatus(): ?string
    {
        return $this->orderStatus;
    }

    public function setOrderStatus(string $orderStatus): self
    {
        $this->orderStatus = $orderStatus;

        return $this;
    }

    public function getFulfillmentChannel(): ?string
    {
        return $this->fulfillmentChannel;
    }

    public function setFulfillmentChannel(?string $fulfillmentChannel): self
    {
        $this->fulfillmentChannel = $fulfillmentChannel;

        return $this;
    }

    public function getSalesChannel(): ?string
    {
        return $this->salesChannel;
    }

    public function setSalesChannel(string $salesChannel): self
    {
        $this->salesChannel = $salesChannel;

        return $this;
    }

    public function getShipServiceLevel(): ?string
    {
        return $this->shipServiceLevel;
    }

    public function setShipServiceLevel(?string $shipServiceLevel): self
    {
        $this->shipServiceLevel = $shipServiceLevel;

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

    public function getItemStatus(): ?string
    {
        return $this->itemStatus;
    }

    public function setItemStatus(?string $itemStatus): self
    {
        $this->itemStatus = $itemStatus;

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

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;

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

    public function getPromotionIds(): ?string
    {
        return $this->promotionIds;
    }

    public function setPromotionIds(?string $promotionIds): self
    {
        $this->promotionIds = $promotionIds;

        return $this;
    }

    public function getFulfilledBy(): ?string
    {
        return $this->fulfilledBy;
    }

    public function setFulfilledBy(?string $fulfilledBy): self
    {
        $this->fulfilledBy = $fulfilledBy;

        return $this;
    }

    public function getItemPrice(): ?float
    {
        return $this->itemPrice;
    }

    public function setItemPrice(?float $itemPrice): self
    {
        $this->itemPrice = $itemPrice;

        return $this;
    }

    public function getItemTax(): ?float
    {
        return $this->itemTax;
    }

    public function setItemTax(?float $itemTax): self
    {
        $this->itemTax = $itemTax;

        return $this;
    }

    public function getShippingPrice(): ?float
    {
        return $this->shippingPrice;
    }

    public function setShippingPrice(?float $shippingPrice): self
    {
        $this->shippingPrice = $shippingPrice;

        return $this;
    }

    public function getShippingTax(): ?float
    {
        return $this->shippingTax;
    }

    public function setShippingTax(?float $shippingTax): self
    {
        $this->shippingTax = $shippingTax;

        return $this;
    }

    public function getGiftWrapPrice(): ?float
    {
        return $this->giftWrapPrice;
    }

    public function setGiftWrapPrice(?float $giftWrapPrice): self
    {
        $this->giftWrapPrice = $giftWrapPrice;

        return $this;
    }

    public function getGiftWrapTax(): ?float
    {
        return $this->giftWrapTax;
    }

    public function setGiftWrapTax(?float $giftWrapTax): self
    {
        $this->giftWrapTax = $giftWrapTax;

        return $this;
    }

    public function getItemPromotionDiscount(): ?float
    {
        return $this->itemPromotionDiscount;
    }

    public function setItemPromotionDiscount(?float $itemPromotionDiscount): self
    {
        $this->itemPromotionDiscount = $itemPromotionDiscount;

        return $this;
    }

    public function getShipPromotionDiscount(): ?float
    {
        return $this->shipPromotionDiscount;
    }

    public function setShipPromotionDiscount(?float $shipPromotionDiscount): self
    {
        $this->shipPromotionDiscount = $shipPromotionDiscount;

        return $this;
    }

    public function getVatExclusiveItemPrice(): ?float
    {
        return $this->vatExclusiveItemPrice;
    }

    public function setVatExclusiveItemPrice(?float $vatExclusiveItemPrice): self
    {
        $this->vatExclusiveItemPrice = $vatExclusiveItemPrice;

        return $this;
    }

    public function getVatExclusiveShippingPrice(): ?float
    {
        return $this->vatExclusiveShippingPrice;
    }

    public function setVatExclusiveShippingPrice(?float $vatExclusiveShippingPrice): self
    {
        $this->vatExclusiveShippingPrice = $vatExclusiveShippingPrice;

        return $this;
    }

    public function getVatExclusiveGiftwrapPrice(): ?float
    {
        return $this->vatExclusiveGiftwrapPrice;
    }

    public function setVatExclusiveGiftwrapPrice(?float $vatExclusiveGiftwrapPrice): self
    {
        $this->vatExclusiveGiftwrapPrice = $vatExclusiveGiftwrapPrice;

        return $this;
    }

    public function getGiftWrapPriceCurrency(): ?float
    {
        return $this->giftWrapPriceCurrency;
    }

    public function setGiftWrapPriceCurrency(?float $giftWrapPriceCurrency): self
    {
        $this->giftWrapPriceCurrency = $giftWrapPriceCurrency;

        return $this;
    }

    public function getGiftWrapTaxCurrency(): ?float
    {
        return $this->giftWrapTaxCurrency;
    }

    public function setGiftWrapTaxCurrency(?float $giftWrapTaxCurrency): self
    {
        $this->giftWrapTaxCurrency = $giftWrapTaxCurrency;

        return $this;
    }

    public function getItemPromotionDiscountCurrency(): ?float
    {
        return $this->itemPromotionDiscountCurrency;
    }

    public function setItemPromotionDiscountCurrency(?float $itemPromotionDiscountCurrency): self
    {
        $this->itemPromotionDiscountCurrency = $itemPromotionDiscountCurrency;

        return $this;
    }

    public function getShipPromotionDiscountCurrency(): ?float
    {
        return $this->shipPromotionDiscountCurrency;
    }

    public function setShipPromotionDiscountCurrency(?float $shipPromotionDiscountCurrency): self
    {
        $this->shipPromotionDiscountCurrency = $shipPromotionDiscountCurrency;

        return $this;
    }

    public function getVatExclusiveItemPriceCurrency(): ?float
    {
        return $this->vatExclusiveItemPriceCurrency;
    }

    public function setVatExclusiveItemPriceCurrency(?float $vatExclusiveItemPriceCurrency): self
    {
        $this->vatExclusiveItemPriceCurrency = $vatExclusiveItemPriceCurrency;

        return $this;
    }

    public function getVatExclusiveShippingPriceCurrency(): ?float
    {
        return $this->vatExclusiveShippingPriceCurrency;
    }

    public function setVatExclusiveShippingPriceCurrency(?float $vatExclusiveShippingPriceCurrency): self
    {
        $this->vatExclusiveShippingPriceCurrency = $vatExclusiveShippingPriceCurrency;

        return $this;
    }

    public function getVatExclusiveGiftwrapPriceCurrency(): ?float
    {
        return $this->vatExclusiveGiftwrapPriceCurrency;
    }

    public function setVatExclusiveGiftwrapPriceCurrency(?float $vatExclusiveGiftwrapPriceCurrency): self
    {
        $this->vatExclusiveGiftwrapPriceCurrency = $vatExclusiveGiftwrapPriceCurrency;

        return $this;
    }

    public function getIntegrated(): ?bool
    {
        return $this->integrated;
    }

    public function setIntegrated(?bool $integrated): self
    {
        $this->integrated = $integrated;

        return $this;
    }

    public function getIntegrationNumber(): ?string
    {
        return $this->integrationNumber;
    }

    public function setIntegrationNumber(?string $integrationNumber): self
    {
        $this->integrationNumber = $integrationNumber;

        return $this;
    }

    public function getIsMultiline(): ?bool
    {
        return $this->isMultiline;
    }

    public function setIsMultiline(?bool $isMultiline): self
    {
        $this->isMultiline = $isMultiline;

        return $this;
    }

    public function getIsReturn(): ?bool
    {
        return $this->isReturn;
    }

    public function setIsReturn(?bool $isReturn): self
    {
        $this->isReturn = $isReturn;

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

    public function getItemPriceCurrency(): ?float
    {
        return $this->itemPriceCurrency;
    }

    public function setItemPriceCurrency(?float $itemPriceCurrency): self
    {
        $this->itemPriceCurrency = $itemPriceCurrency;

        return $this;
    }

    public function getItemTaxCurrency(): ?float
    {
        return $this->itemTaxCurrency;
    }

    public function setItemTaxCurrency(?float $itemTaxCurrency): self
    {
        $this->itemTaxCurrency = $itemTaxCurrency;

        return $this;
    }

    public function getShippingPriceCurrency(): ?float
    {
        return $this->shippingPriceCurrency;
    }

    public function setShippingPriceCurrency(?float $shippingPriceCurrency): self
    {
        $this->shippingPriceCurrency = $shippingPriceCurrency;

        return $this;
    }

    public function getShippingTaxCurrency(): ?float
    {
        return $this->shippingTaxCurrency;
    }

    public function setShippingTaxCurrency(?float $shippingTaxCurrency): self
    {
        $this->shippingTaxCurrency = $shippingTaxCurrency;

        return $this;
    }
}
