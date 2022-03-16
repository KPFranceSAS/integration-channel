<?php

namespace App\Service\OwletCare;

use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Helper\BusinessCentral\Model\SaleOrder;
use App\Helper\BusinessCentral\Model\SaleOrderLine;
use App\Helper\Utils\DatetimeUtils;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\Carriers\GetTracking;
use App\Service\Integrator\IntegratorParent;
use App\Service\MailService;
use App\Service\OwletCare\OwletCareApi;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class OwletCareIntegrateOrder extends IntegratorParent

{

    protected $businessCentralConnector;

    protected $owletCareApi;


    public function __construct(
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        OwletCareApi $owletCareApi,
        BusinessCentralAggregator $businessCentralAggregator
    ) {
        parent::__construct($manager, $logger, $mailer, $businessCentralAggregator);
        $this->owletCareApi = $owletCareApi;
    }


    public function getChannel()
    {
        return WebOrder::CHANNEL_OWLETCARE;
    }

    /**
     * process all invocies directory
     *
     * @return void
     */
    public function integrateAllOrders()
    {
        $counter = 0;
        $ordersApi = $this->owletCareApi->getAllOrdersToSend();

        foreach ($ordersApi as $orderApi) {
            if ($this->integrateOrder($orderApi)) {
                $counter++;
                $this->logger->info("Orders integrated : $counter ");
            }
        }
    }


    protected function getOrderId($orderApi)
    {
        return $orderApi['order_number'];
    }

    public function getCompanyIntegration($orderApi)
    {
        return BusinessCentralConnector::KIT_PERSONALIZACION_SPORT;
    }


    public function transformToAnBcOrder($orderApi): SaleOrder
    {
        $orderBC = new SaleOrder();
        $orderBC->customerNumber = '130803';

        $dateCreated = DatetimeUtils::transformFromIso8601($orderApi['processed_at']);
        $dateCreated->add(new \DateInterval('P3D'));
        $orderBC->requestedDeliveryDate = $dateCreated->format('Y-m-d');
        $orderBC->locationCode = WebOrder::DEPOT_CENTRAL;
        $orderBC->billToName = $orderApi['billing_address']['name'];
        $orderBC->shipToName = $orderApi['shipping_address']['name'];


        $this->transformAddress($orderBC, $orderApi['shipping_address'], 'shipping');
        $this->transformAddress($orderBC, $orderApi['billing_address'], 'selling');


        if ($orderApi['currency'] != 'EUR') {
            $orderBC->currencyCode =  $orderApi['currency'];
        }



        if (strlen($orderApi['shipping_address']["phone"]) > 0) {
            $orderBC->phoneNumber = $orderApi['shipping_address']["phone"];
        } elseif (strlen($orderApi['billing_address']["phone"]) > 0) {
            $orderBC->phoneNumber = $orderApi['billing_address']["phone"];
        }

        $orderBC->externalDocumentNumber = (string)$orderApi['order_number'];
        $orderBC->email = $orderApi['email'];

        $orderBC->pricesIncludeTax = true; // enables BC to do VAT autocalculation
        $orderBC->salesLines = $this->getSalesOrderLines($orderApi);



        $livraisonFees = floatval($orderApi['total_shipping_price_set']['shop_money']['amount']);
        // ajout livraison 
        $company = $this->getCompanyIntegration($orderApi);


        foreach ($orderApi['shipping_lines'] as $line) {
            if (floatval($line['price']) > 0) {
                $account = $this->getBusinessCentralConnector($company)->getAccountForExpedition();
                $saleLineDelivery = new SaleOrderLine();
                $saleLineDelivery->lineType = SaleOrderLine::TYPE_GLACCOUNT;
                $saleLineDelivery->quantity = 1;
                $saleLineDelivery->accountId = $account['id'];
                $saleLineDelivery->unitPrice = floatval($line['price']);
                $saleLineDelivery->description = 'GASTOS DE ENVIO ' . strtoupper($line['code']);
                $orderBC->salesLines[] = $saleLineDelivery;
            }
        }


        $discounts = $this->getAllDiscountLines($orderApi);

        if (count($discounts) > 0) {
            $account = $this->getBusinessCentralConnector($company)->getAccountByNumber('7000005');
            foreach ($discounts as $discount) {
                $saleLineDelivery = new SaleOrderLine();
                $saleLineDelivery->lineType = SaleOrderLine::TYPE_GLACCOUNT;
                $saleLineDelivery->quantity = 1;
                $saleLineDelivery->accountId = $account['id'];
                $saleLineDelivery->unitPrice = -$discount['value'];
                $saleLineDelivery->description = strtoupper($discount['description']);
                $orderBC->salesLines[] = $saleLineDelivery;
            }
        }

        return $orderBC;
    }



    private function getDescriptionDiscount($discountApplication)
    {
        if (array_key_exists('code', $discountApplication)) {
            return 'CODE ' . $discountApplication['code'];
        } elseif (array_key_exists('title', $discountApplication)) {
            return $discountApplication['title'];
        } elseif (array_key_exists('description', $discountApplication)) {
            return $discountApplication['description'];
        } else {
            return '';
        }
    }


    private function getValueDiscount($discountApplication)
    {
        if ($discountApplication['value_type'] == 'percentage') {
            return $discountApplication['value'] . '%';
        } else {
            return $discountApplication['value'] . 'EUR';
        }
    }




    private function getAllDiscountLines($orderApi)
    {
        $discounts = [];
        foreach ($orderApi['line_items'] as $line) {

            foreach ($line["discount_allocations"] as $discountLine) {
                $discountApplication = $orderApi["discount_applications"][$discountLine["discount_application_index"]];
                $discounts[] = [
                    'value' => floatval($discountLine['amount']),
                    'description' => 'DISCUENTO ' .  $line["sku"] . " / " . $this->getValueDiscount($discountApplication) . " / " . $this->getDescriptionDiscount($discountApplication)
                ];
            }
        }


        foreach ($orderApi['shipping_lines'] as $line) {

            foreach ($line["discount_allocations"] as $discountLine) {
                $discountApplication = $orderApi["discount_applications"][$discountLine["discount_application_index"]];
                $discounts[] = [
                    'value' => floatval($discountLine['amount']),
                    'description' => "DISCUENTO GASTOS DE ENVIO / " . $this->getValueDiscount($discountApplication) . " / " . $this->getDescriptionDiscount($discountApplication)
                ];
            }
        }
        return $discounts;
    }



    private function transformAddress(SaleOrder $saleOrder, array $addressShopifyType, string $addressBusinessType)
    {
        $adress =  trim($addressShopifyType['address1']);
        if (strlen($addressShopifyType['address2']) > 0) {
            $adress .= ', ' . trim($addressShopifyType['address2']);
        }

        $adress = $this->simplifyAddress($adress);

        if (strlen($adress) < 100) {
            $saleOrder->{$addressBusinessType . "PostalAddress"}->street = $adress;
        } else {
            $saleOrder->{$addressBusinessType . "PostalAddress"}->street = substr($adress, 0, 100) . "\r\n" . substr($adress, 99);
        }

        $saleOrder->{$addressBusinessType . "PostalAddress"}->city = substr($addressShopifyType['city'], 0, 100);
        $saleOrder->{$addressBusinessType . "PostalAddress"}->postalCode = $addressShopifyType['zip'];
        $saleOrder->{$addressBusinessType . "PostalAddress"}->countryLetterCode = $addressShopifyType['country_code'];
        if (strlen($addressShopifyType['province']) > 0) {
            $saleOrder->{$addressBusinessType . "PostalAddress"}->state = substr($addressShopifyType['province'], 0, 30);;
        }
    }






    private function getSalesOrderLines($orderApi): array
    {
        $saleOrderLines = [];
        $company = $this->getCompanyIntegration($orderApi);

        foreach ($orderApi['line_items'] as $line) {

            $saleLine = new SaleOrderLine();
            $saleLine->lineType = SaleOrderLine::TYPE_ITEM;
            $sku = $line['sku'];
            $saleLine->itemId = $this->getProductCorrelationSku($sku, $company);
            $saleLine->unitPrice = floatval($line["price"]);
            $saleLine->quantity = $line["quantity"];
            $saleOrderLines[] = $saleLine;
        }


        return $saleOrderLines;
    }
}
