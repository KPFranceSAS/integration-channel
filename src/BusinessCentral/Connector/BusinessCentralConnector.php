<?php

namespace App\BusinessCentral\Connector;

use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

abstract class BusinessCentralConnector
{
    public const KP_FRANCE = "KP FRANCE";

    public const GADGET_IBERIA = "GADGET IBERIA SL";

    public const KIT_PERSONALIZACION_SPORT = "KIT PERSONALIZACION SPORT SL";

    public const INIA = "INIA SLU";

    public const KP_UK = "KP UK";


    public const EP_ACCOUNT = "accounts";

    public const EP_COMPANIES = "companies";

    public const EP_SHIPMENT_METHODS = "shipmentMethods";

    public const EP_PAYMENT_METHODS = "paymentMethods";

    public const EP_PAYMENT_TERMS = "paymentTerms";

    public const EP_CUSTOMERS = "customers";

    public const EP_CUSTOMER_PAYMENT_JOURNALS = "customerPaymentJournals";

    public const EP_CUSTOMER_PAYMENTS = "customerPayments";

    public const EP_ITEMS = "items";

    public const EP_STOCK_PRODUCTS = "itemAvailabilities";

    public const EP_SALES_ORDERS = "salesOrders";

    public const EP_SALES_ORDERS_LINE = "salesOrderLines";

    public const EP_STATUS_ORDERS = "statusOrders";

    public const EP_SALES_INVOICES = "salesInvoices";

    public const EP_SALES_INVOICES_LINES = "salesInvoiceLines";

    public const EP_SALES_CREDITS_LINE = "salesCreditMemoLines";

    public const EP_SALES_CREDITS = "salesCreditMemos";

    public const EP_SALES_RETURNS_LINE = "SalesReturnLine";

    public const EP_SALES_RETURNS = "SalesReturnHeader";

    public const EP_PURCHASES_INVOICES_LINE = "purchaseInvoiceLines";

    public const EP_PURCHASES_INVOICES = "purchaseInvoices";

    public const EP_PURCHASES_ORDERS = "purchaseOrders";

    public const EP_TRANSFER_ORDERS = "transferOrders";

    public const EP_BUNDLE_CONTENT = "billOfMaterials";
    
    public const EP_FEES_TAXES = "FeesAndTaxes";
    


    protected $logger;

    protected $debugger;

    protected $client;

    protected $companyId;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger,
        string $urlBC,
        string $loginBC,
        string $passwordBC,
        string $appEnv
    ) {
        $this->logger = $logger;
        $this->client = new Client([
            'base_uri' => $urlBC . '/api/v1.0/',
            'headers'  => [
                'User-Agent'    => 'Business Central SDK',
                'Authorization' => "Basic " . base64_encode("$loginBC:$passwordBC"),
            ],
        ]);
        $this->debugger = false;
    }


    abstract public function getCompanyIntegration();

    abstract protected function getAccountNumberForExpedition();



    public function doDeleteRequest(string $endPoint): bool
    {
        $response = $this->client->request(
            'DELETE',
            self::EP_COMPANIES . '(' . $this->getCompanyId() . ')/' . $endPoint,
            [
                'debug' => $this->debugger
            ]
        );

        return $response->getStatusCode() == '204';
    }



    public function doPostRequest(string $endPoint, array $json, array $query = []): ?array
    {
        if ($this->debugger) {
            $this->logger->info(json_encode($json));
        }

        $response = $this->client->request(
            'POST',
            self::EP_COMPANIES . '(' . $this->getCompanyId() . ')/' . $endPoint,
            [
                'query' =>  $query,
                'json' => $json,
                'headers' => ['Content-Type' => 'application/json'],
                'debug' => $this->debugger
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }



    public function doPatchRequest(string $endPoint, string $etag, array $json, array $query = []): ?array
    {
        if ($this->debugger) {
            $this->logger->info(json_encode($json));
        }

        $response = $this->client->request(
            'PATCH',
            self::EP_COMPANIES . '(' . $this->getCompanyId() . ')/' . $endPoint,
            [
                'query' =>  $query,
                'json' => $json,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'If-Match' => $etag,
                ],
                'debug' => $this->debugger
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }


    public function doGetRequest(string $endPoint, array $query = [])
    {
        $endPoint = $endPoint === self::EP_COMPANIES
            ? self::EP_COMPANIES
            : self::EP_COMPANIES . '(' . $this->getCompanyId() . ')/' . $endPoint;

        $response = $this->client->request(
            'GET',
            $endPoint,
            [
                'query' =>  $query,
                'headers' => ['Content-Type' => 'application/json'],
                'debug' => $this->debugger
            ]
        );
        return json_decode($response->getBody()->getContents(), true);
    }


    public function downloadStream(string $endPoint): string
    {
        $response = $this->client->request(
            'GET',
            $endPoint,
            [
                'debug' => $this->debugger
            ]
        );
        return $response->getBody()->getContents();
    }


    public function getElementsByArray(
        string $type,
        ?string $filters,
        bool $all = false,
        array $paramSupps = []
    ) {
        $query = [];
        if ($filters) {
            $query = [
                '$filter' => $filters
            ];
        }
        foreach ($paramSupps as $keyParam => $valParam) {
            $query[$keyParam] = $valParam;
        }
        $items =  $this->doGetRequest($type, $query)['value'];
        if ($all) {
            return $items;
        } else {
            if (count($items) > 0) {
                return reset($items);
            } else {
                return null;
            }
        }
    }


    public function getElementByNumber(
        string $type,
        string $number,
        string $filter = 'number',
        array $paramSupps = []
    ) : ?array {
        $query = [
            '$filter' => "$filter eq '$number'"
        ];
        foreach ($paramSupps as $keyParam => $valParam) {
            $query[$keyParam] = $valParam;
        }
        $items =  $this->doGetRequest($type, $query)['value'];
        if (count($items) > 0) {
            return reset($items);
        } else {
            return null;
        }
    }


    public function getElementById(
        string $type,
        string $id,
        array $paramSupps = []
    ) {
        try {
            $item =  $this->doGetRequest($type . '(' . $id . ')', $paramSupps);
            return $item;
        } catch (Exception $e) {
            throw new Exception("No $type in the database with id equal to $id. You need to add a corelation");
        }
    }



    /**
     * Company
     */
    public function getCompanyName()
    {
        return $this->getCompanyIntegration();
    }


    public function getCompanyId(): string
    {
        if (!$this->companyId) {
            $this->selectCompany($this->getCompanyIntegration());
        }
        return $this->companyId;
    }

    public function selectCompany(string $name): string
    {
        $companies = $this->getCompanies();
        foreach ($companies as $company) {
            if (strtoupper($company['name']) == $name) {
                $this->companyId = $company['id'];
                return $company['id'];
            }
        }
        throw new Exception($name . ' not found');
    }


    public function getCompanies()
    {
        $response =  $this->doGetRequest(self::EP_COMPANIES);
        return $response['value'];
    }


    /**
     * Item
     */
    public function getItemByNumber(string $sku)
    {
        return $this->getElementByNumber(self::EP_ITEMS, $sku);
    }

    public function getItem(string $id)
    {
        return $this->getElementById(self::EP_ITEMS, $id);
    }
    



    public function getComponentsBundle(string $sku)
    {
        return $this->getElementsByArray(
            self::EP_BUNDLE_CONTENT,
            "parentItemNo eq '$sku'",
            true
        );
    }




    public function getStockAvailabilityPerProduct(string $sku)
    {
        return $this->getElementsByArray(
            self::EP_STOCK_PRODUCTS,
            "no eq '$sku'",
        );
    }


    /**
     * Sale order
     */
    public function createSaleOrder(array $order): ?array
    {
        return $this->doPostRequest(
            self::EP_SALES_ORDERS,
            $order
        );
    }



    /**
     * Sale order
     */

    public function updateSaleOrder(string $id, string $etag, array $order): ?array
    {
        return $this->doPatchRequest(
            self::EP_SALES_ORDERS . '(' . $id . ')',
            $etag,
            $order
        );
    }


    public function createSaleOrderLine(string $orderId, array $orderLine): ?array
    {
        return $this->doPostRequest(
            self::EP_SALES_ORDERS . "($orderId)/" . self::EP_SALES_ORDERS_LINE,
            $orderLine
        );
    }


    public function getFullSaleOrder(string $id): ?array
    {
        return  $this->getElementById(
            self::EP_SALES_ORDERS,
            $id,
            ['$expand' => 'salesOrderLines,customer']
        );
    }

    public function getFullSaleOrderByNumber(string $number): ?array
    {
        return $this->getElementByNumber(
            self::EP_SALES_ORDERS,
            $number,
            'number',
            ['$expand' => 'salesOrderLines,customer']
        );
    }


    public function getStatusOrderByNumber(string $number): ?array
    {
        return  $this->getElementByNumber(
            self::EP_STATUS_ORDERS,
            $number,
            'number',
            ['$expand' => 'statusOrderLines']
        );
    }


    public function getSaleOrder(string $id): ?array
    {
        return $this->getElementById(self::EP_SALES_ORDERS, $id);
    }

    public function getSaleOrderByNumber(string $number): ?array
    {
        return $this->getElementByNumber(self::EP_SALES_ORDERS, $number);
    }

    public function getSaleOrderByExternalNumber(string $number): ?array
    {
        return $this->getElementByNumber(self::EP_SALES_ORDERS, $number, 'externalDocumentNumber');
    }


    public function getAllSalesLineForOrder(string $orderId): ?array
    {
        return $this->doGetRequest(self::EP_SALES_ORDERS . "($orderId)/" . self::EP_SALES_ORDERS_LINE)["value"];
    }


    public function getSaleLineOrder(string $orderId, string $id): ?array
    {
        return $this->getElementById(self::EP_SALES_ORDERS . "($orderId)/" . self::EP_SALES_ORDERS_LINE, $id);
    }


    public function getSaleOrderByExternalNumberAndCustomer(string $number, string $customer)
    {
        $query = [
            '$filter' => "externalDocumentNumber eq '$number' and customerNumber eq '$customer' "
        ];
        $items =  $this->doGetRequest(self::EP_SALES_ORDERS, $query)['value'];
        if (count($items) > 0) {
            return reset($items);
        } else {
            return null;
        }
    }


    public function getAllSalesOrderByCustomer(string $customer)
    {
        $query = [
            '$filter' => "customerNumber eq '$customer' "
        ];
        return $this->doGetRequest(self::EP_SALES_ORDERS, $query)['value'];
    }


    public function deleteSaleOrder($idOrder)
    {
        return $this->doDeleteRequest(self::EP_SALES_ORDERS . "($idOrder)");
    }

    /**
     * Shipment methods
     */
    public function getAllShipmentMethods()
    {
        return $this->doGetRequest(self::EP_SHIPMENT_METHODS);
    }


    public function getShipmentMethodByCode(string $code)
    {
        return $this->getElementsByArray(self::EP_SHIPMENT_METHODS, "code eq '$code' ");
    }

    /**
     * Payment methods
     */
    public function getAllPaymentMethods(): array
    {
        return $this->doGetRequest(self::EP_PAYMENT_METHODS);
    }


    public function getPaymentMethodByCode(string $code): ?array
    {
        return $this->getElementsByArray(self::EP_PAYMENT_METHODS, "code eq '$code' ");
    }


    /**
     * Payment terms
     */
    public function getAllPaymentTerms(): array
    {
        return $this->doGetRequest(self::EP_PAYMENT_TERMS);
    }

    public function getPaymentTermByCode(string $code): ?array
    {
        return $this->getElementsByArray(self::EP_PAYMENT_TERMS, "code eq '$code' ");
    }


    /**
     * Customers
     */
    public function getAllCustomers(): array
    {
        return $this->doGetRequest(self::EP_CUSTOMERS);
    }


    public function getCustomer(string $id): ?array
    {
        return $this->getElementById(self::EP_CUSTOMERS, $id);
    }


    public function getCustomerByNumber(string $number): ?array
    {
        return $this->getElementByNumber(
            self::EP_CUSTOMERS,
            $number,
            'number',
            ['$expand' => 'customerFinancialDetails']
        );
    }

    /**
     * Account
     */
    public function getAccountByNumber(string $number): ?array
    {
        return $this->getElementByNumber(self::EP_ACCOUNT, $number);
    }

    public function getAccountForExpedition(): ?array
    {
        return $this->getAccountByNumber($this->getAccountNumberForExpedition());
    }




    /**
     * Sale invoice
     */
    public function getSaleInvoice(string $id): ?array
    {
        return $this->getElementById(self::EP_SALES_INVOICES, $id);
    }


    public function getFullSaleInvoiceByNumber(string $number): ?array
    {
        return $this->getElementByNumber(
            self::EP_SALES_INVOICES,
            $number,
            'number',
            ['$expand' => 'salesInvoiceLines,customer']
        );
    }



    public function getTransfersOrderToFba(): array
    {
        $filters = "status eq 'Lanzado' and transferToCode eq 'AMAZON'";
        return $this->getElementsByArray(self::EP_TRANSFER_ORDERS, $filters, true, ['$expand' => 'transferOrderLines']);
    }


    public function getSaleInvoiceByNumber(string $number): ?array
    {
        return $this->getElementByNumber(
            self::EP_SALES_INVOICES,
            $number
        );
    }

    public function getSaleInvoiceByExternalNumber(string $number): ?array
    {
        return $this->getElementByNumber(
            self::EP_SALES_INVOICES,
            $number,
            'externalDocumentNumber'
        );
    }

    public function getSaleInvoiceByOrderNumber(string $number): ?array
    {
        return $this->getElementByNumber(
            self::EP_SALES_INVOICES,
            $number,
            'orderNumber',
            ['$expand' => 'salesInvoiceLines,customer']
        );
    }

    public function getContentInvoicePdf(string $id): string
    {
        $value = $this->doGetRequest(self::EP_SALES_INVOICES . "($id)/pdfDocument")["value"];
        $firstValue = reset($value);
        return $this->downloadStream($firstValue['content@odata.mediaReadLink']);
    }


    public function getSaleInvoiceByExternalDocumentNumberCustomer(
        string $number,
        string $customerNumber
    ): ?array {
        $filters = "externalDocumentNumber eq '$number' and customerNumber eq '$customerNumber' ";
        return $this->getElementsByArray(
            self::EP_SALES_INVOICES,
            $filters,
            false,
            ['$expand' => 'salesInvoiceLines,customer']
        );
    }







    /**
     * Sale return order
     */
    public function createSaleReturnOrder(array $order): ?array
    {
        return $this->doPostRequest(
            self::EP_SALES_RETURNS,
            $order
        );
    }



     /**
     * Sale return order
     */
    public function createSaleReturnOrderLine(array $orderLine): ?array
    {
        return $this->doPostRequest(
            self::EP_SALES_RETURNS_LINE,
            $orderLine
        );
    }






    public function getSaleReturnByNumber(string $number): ?array
    {
        return $this->getSaleReturnBy("number eq '$number'");
    }

    public function getSaleReturnBy(string $condition): ?array
    {
        $return = $this->getElementsByArray(
            self::EP_SALES_RETURNS,
            "$condition",
            false,
            ['$expand' => 'customer']
        );

        if($return){
            $documentNo =$return['no'];
            $return['salesReturnOrderLines'] = $this->getElementsByArray(
                self::EP_SALES_RETURNS_LINE,
                "documentNo eq '$documentNo'",
                true
            );
        }
        return $return;
    }


    public function getSaleReturnByInvoiceAndLpn($invoiceNumber, $lpn): ?array
    {
        return $this->getSaleReturnBy("correctedInvoiceNo eq '$invoiceNumber' and packageTrackingNo eq '$lpn'");
    }




    public function getSaleReturnByExternalNumber(string $number): ?array
    {
        return $this->getSaleReturnBy("externalDocumentNo  eq '$number'");
    }


    public function getSaleReturnByInvoice(string $number): ?array
    {
        return $this->getSaleReturnBy("correctedInvoiceNo eq '$number'");
    }


    public function getSaleReturnByPackageTrackingNo(string $number): ?array
    {
        return $this->getSaleReturnBy("packageTrackingNo eq '$number'");
    }



    public function getSaleReturnByLpnAndExternalNumber(string $lpn, string $number): array
    {
        return $this->getSaleReturnBy("packageTrackingNo eq '$lpn' and externalDocumentNo eq '$number'");
    }




    public function getAllCustomerPaymentJournals(): array
    {
        return $this->getElementsByArray(
            self::EP_CUSTOMER_PAYMENT_JOURNALS,
            null,
            true
        );
    }


    public function getCustomerPaymentJournalByCode(string $code): ?array
    {
        $customerPayementJournals = $this->getAllCustomerPaymentJournals();
        foreach ($customerPayementJournals as $customerJournal) {
            if ($customerJournal['code'] == $code) {
                return $customerJournal;
            }
        }
        return null;
    }



    public function createCustomerPayment(string $customerJournal, array $payment): ?array
    {
        return $this->doPostRequest(
            self::EP_CUSTOMER_PAYMENT_JOURNALS . "($customerJournal)/" . self::EP_CUSTOMER_PAYMENTS,
            $payment
        );
    }


    public function getAllCustomerPaymentJournalByJournal(string $customerJournal): ?array
    {
        return $this->getElementsByArray(
            self::EP_CUSTOMER_PAYMENT_JOURNALS . "($customerJournal)/" . self::EP_CUSTOMER_PAYMENTS,
            null,
            true
        );
    }





    public function getAllTaxes(): ?array
    {
        return $this->getElementsByArray(self::EP_FEES_TAXES, null, true);
    }



    public function getTaxesByCodeAndByFeeType($code, $feeType): ?array
    {
        return $this->getElementsByArray(self::EP_FEES_TAXES, "FeeType eq '$feeType' and Code eq '$code'");
    }
}
