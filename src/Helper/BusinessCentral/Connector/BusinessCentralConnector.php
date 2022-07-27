<?php

namespace App\Helper\BusinessCentral\Connector;

use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

abstract class BusinessCentralConnector
{
    public const KP_FRANCE = "KP FRANCE";

    public const GADGET_IBERIA = "GADGET IBERIA SL";

    public const KIT_PERSONALIZACION_SPORT = "KIT PERSONALIZACION SPORT SL";

    public const INIA = "INIA SLU";








    public const EP_ACCOUNT = "accounts";

    public const EP_COMPANIES = "companies";

    public const EP_SHIPMENT_METHODS = "shipmentMethods";

    public const EP_PAYMENT_METHODS = "paymentMethods";

    public const EP_PAYMENT_TERMS = "paymentTerms";

    public const EP_CUSTOMERS = "customers";

    public const EP_ITEMS = "items";

    public const EP_STOCK_PRODUCTS = "itemStocks";

    public const EP_SALES_ORDERS = "salesOrders";

    public const EP_SALES_ORDERS_LINE = "salesOrderLines";

    public const EP_STATUS_ORDERS = "statusOrders";

    public const EP_SALES_INVOICES = "salesInvoices";

    public const EP_SALES_INVOICES_LINES = "salesInvoiceLines";

    public const EP_SALES_CREDITS_LINE = "salesCreditMemoLines";

    public const EP_SALES_CREDITS = "salesCreditMemos";

    public const EP_SALES_RETURNS_LINE = "salesReturnOrderLines";

    public const EP_SALES_RETURNS = "salesReturnOrders";


    public const EP_PURCHASES_INVOICES_LINE = "purchaseInvoiceLines";

    public const EP_PURCHASES_INVOICES = "purchaseInvoices";

    public const EP_PURCHASES_ORDERS = "purchaseOrders";


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
        $this->debugger = ($appEnv == 'dev' || $appEnv == 'test');
    }


    abstract protected function getCompanyIntegration();

    abstract protected function getAccountNumberForExpedition();



    public function doDeleteRequest(string $endPoint)
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



    public function doPostRequest(string $endPoint, array $json, array $query = [])
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



    public function doPatchRequest(string $endPoint, string $etag, array $json, array $query = [])
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
    ) {
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

    public function getStockPerProduct(string $sku)
    {
        return $this->getElementsByArray(
            self::EP_STOCK_PRODUCTS,
            '',
            true,
            ['$expand' => 'stockProductosLines($filter = ' . "itemNo eq '$sku'  )"]
        );
    }

    public function getStockPerProductPerLocation(string $sku, string $location)
    {
        return $this->getElementsByArray(
            self::EP_STOCK_PRODUCTS,
            '',
            true,
            ['$expand' => 'stockProductosLines($filter = ' . "itemNo eq '$sku' and locationFilter eq '$location' )"]
        );
    }

    /**
     * Sale order
     */

    public function createSaleOrder(array $order)
    {
        return $this->doPostRequest(
            self::EP_SALES_ORDERS,
            $order
        );
    }



    /**
     * Sale order
     */

    public function updateSaleOrder(string $id, string $etag, array $order)
    {
        return $this->doPatchRequest(
            self::EP_SALES_ORDERS . '(' . $id . ')',
            $etag,
            $order
        );
    }


    public function createSaleOrderLine(string $orderId, array $orderLine)
    {
        return $this->doPostRequest(
            self::EP_SALES_ORDERS . "($orderId)/" . self::EP_SALES_ORDERS_LINE,
            $orderLine
        );
    }


    public function getFullSaleOrder(string $id)
    {
        return  $this->getElementById(
            self::EP_SALES_ORDERS,
            $id,
            ['$expand' => 'salesOrderLines,customer']
        );
    }

    public function getFullSaleOrderByNumber(string $number)
    {
        return $this->getElementByNumber(
            self::EP_SALES_ORDERS,
            $number,
            'number',
            ['$expand' => 'salesOrderLines,customer']
        );
    }


    public function getStatusOrderByNumber(string $number)
    {
        return  $this->getElementByNumber(
            self::EP_STATUS_ORDERS,
            $number,
            'number',
            ['$expand' => 'statusOrderLines']
        );
    }


    public function getSaleOrder(string $id)
    {
        return $this->getElementById(self::EP_SALES_ORDERS, $id);
    }

    public function getSaleOrderByNumber(string $number)
    {
        return $this->getElementByNumber(self::EP_SALES_ORDERS, $number);
    }

    public function getSaleOrderByExternalNumber(string $number)
    {
        return $this->getElementByNumber(self::EP_SALES_ORDERS, $number, 'externalDocumentNumber');
    }


    public function getAllSalesLineForOrder(string $orderId)
    {
        return $this->doGetRequest(self::EP_SALES_ORDERS . "($orderId)/" . self::EP_SALES_ORDERS_LINE)["value"];
    }


    public function getSaleLineOrder(string $orderId, string $id)
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
    public function getAllPaymentMethods()
    {
        return $this->doGetRequest(self::EP_PAYMENT_METHODS);
    }


    public function getPaymentMethodByCode(string $code)
    {
        return $this->getElementsByArray(self::EP_PAYMENT_METHODS, "code eq '$code' ");
    }


    /**
     * Payment terms
     */
    public function getAllPaymentTerms()
    {
        return $this->doGetRequest(self::EP_PAYMENT_TERMS);
    }

    public function getPaymentTermByCode(string $code)
    {
        return $this->getElementsByArray(self::EP_PAYMENT_TERMS, "code eq '$code' ");
    }


    /**
     * Customers
     */
    public function getAllCustomers()
    {
        return $this->doGetRequest(self::EP_CUSTOMERS);
    }


    public function getCustomer(string $id)
    {
        return $this->getElementById(self::EP_CUSTOMERS, $id);
    }


    public function getCustomerByNumber(string $number)
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
    public function getAccountByNumber(string $number)
    {
        return $this->getElementByNumber(self::EP_ACCOUNT, $number);
    }

    public function getAccountForExpedition()
    {
        return $this->getAccountByNumber($this->getAccountNumberForExpedition());
    }


    /**
     * Sale invoice
     */
    public function getSaleInvoice(string $id)
    {
        return $this->getElementById(self::EP_SALES_INVOICES, $id);
    }


    public function getFullSaleInvoiceByNumber(string $number)
    {
        return $this->getElementByNumber(
            self::EP_SALES_INVOICES,
            $number,
            'number',
            ['$expand' => 'salesInvoiceLines,customer']
        );
    }


    public function getSaleInvoiceByNumber(string $number)
    {
        return $this->getElementByNumber(
            self::EP_SALES_INVOICES,
            $number
        );
    }

    public function getSaleInvoiceByExternalNumber(string $number)
    {
        return $this->getElementByNumber(
            self::EP_SALES_INVOICES,
            $number,
            'externalDocumentNumber'
        );
    }

    public function getSaleInvoiceByOrderNumber(string $number)
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
    ) {
        $filters = "externalDocumentNumber eq '$number' and customerNumber eq '$customerNumber' ";
        return $this->getElementsByArray(
            self::EP_SALES_INVOICES,
            $filters,
            false,
            ['$expand' => 'salesInvoiceLines,customer']
        );
    }


    public function getSaleReturnByNumber(string $number)
    {
        return $this->getElementByNumber(
            self::EP_SALES_RETURNS,
            $number
        );
    }

    public function getSaleReturnBy(string $condition, string $number)
    {
        return $this->getElementsByArray(
            self::EP_SALES_RETURNS,
            "$condition eq '$number'",
            false,
            ['$expand' => 'salesReturnOrderLines,customer']
        );
    }


    public function getSaleReturnByExternalNumber(string $number)
    {
        return $this->getSaleReturnBy("externalDocumentNo", $number);
    }


    public function getSaleReturnByInvoice(string $number)
    {
        return $this->getSaleReturnBy("correctedInvoiceNo", $number);
    }


    public function getSaleReturnByPackageTrackingNo(string $number)
    {
        return $this->getSaleReturnBy("packageTrackingNo", $number);
    }



    public function getSaleReturnByLpnAndExternalNumber(string $lpn, string $number)
    {
        return $this->getElementsByArray(
            self::EP_SALES_RETURNS,
            "packageTrackingNo eq '$lpn' and externalDocumentNo eq '$number'",
            false,
            ['$expand' => 'salesReturnOrderLines,customer']
        );
    }


    /**
     * purchase Orders
     *
     */
    public function getPurchaseInvoicesByItemNumber(string $number)
    {
        return $this->getElementsByArray(
            self::EP_PURCHASES_ORDERS,
            null,
            true
        );
    }
}
