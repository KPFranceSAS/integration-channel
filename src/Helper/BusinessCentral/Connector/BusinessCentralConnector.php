<?php

namespace App\Helper\BusinessCentral\Connector;

use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

abstract class BusinessCentralConnector
{
    const KP_FRANCE = "KP FRANCE";

    const GADGET_IBERIA = "GADGET IBERIA SL";

    const KIT_PERSONALIZACION_SPORT = "KIT PERSONALIZACION SPORT SL";

    const INIA = "INIA SLU";




    const EP_ITEMS = "items";

    const EP_CUSTOMERS = "customers";

    const EP_SALES_ORDERS = "salesOrders";

    const EP_SALES_INVOICES = "salesInvoices";

    const EP_SALES_INVOICES_LINES = "salesInvoiceLines";

    const EP_ACCOUNT = "accounts";

    const EP_COMPANIES = "companies";

    const EP_SALES_ORDERS_LINE = "salesOrderLines";

    protected $logger;

    protected $debugger;

    protected $companyId;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger, string $urlBC, string  $loginBC, string  $passwordBC, string $appEnv)
    {
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


    abstract protected function getCompanyIntegration();

    abstract protected function getAccountNumberForExpedition();



    public function selectCompany(string $name): string
    {
        $companies = $this->getCompanies();
        foreach ($companies as $company) {
            if (strtoupper($company['name']) == $name) {
                $this->companyId = $company['id'];
                return $company['id'];
            }
        }
        throw new \Exception($name . ' not found');
    }


    public function getAccountForExpedition()
    {
        return $this->getAccountByNumber($this->getAccountNumberForExpedition());
    }


    public function getCompanies()
    {
        $response =  $this->doGetRequest(self::EP_COMPANIES);
        return $response['value'];
    }




    public function deleteSaleOrder($idOrder)
    {
        return $this->doDeleteRequest(self::EP_SALES_ORDERS . "($idOrder)");
    }


    public function doDeleteRequest(string $endPoint)
    {
        $response = $this->client->request('DELETE', self::EP_COMPANIES . '(' . $this->getCompanyId() . ')/' . $endPoint, [
            'debug' => $this->debugger
        ]);

        return $response->getStatusCode() == '204';
    }



    public function doPostRequest(string $endPoint, array $json, array $query = [])
    {
        if ($this->debugger) {
            $this->logger->info(json_encode($json));
        }

        $response = $this->client->request('POST', self::EP_COMPANIES . '(' . $this->getCompanyId() . ')/' . $endPoint, [
            'query' =>  $query,
            'json' => $json,
            'headers' => ['Content-Type' => 'application/json'],
            'debug' => $this->debugger
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }


    public function doGetRequest(string $endPoint, array $query = [])
    {
        $endPoint = $endPoint === self::EP_COMPANIES ? self::EP_COMPANIES : self::EP_COMPANIES . '(' . $this->getCompanyId() . ')/' . $endPoint;
        $response = $this->client->request('GET', $endPoint, [
            'query' =>  $query,
            'headers' => ['Content-Type' => 'application/json'],
            'debug' => $this->debugger
        ]);
        return json_decode($response->getBody()->getContents(), true);
    }



    public function downloadStream(string $endPoint): string
    {
        $response = $this->client->request('GET', $endPoint, [
            'debug' => $this->debugger
        ]);
        return $response->getBody()->getContents();
    }



    public function getElementByNumber(string $type, string $number, string $filter = 'number', array $paramSupps = [])
    {
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


    public function getElementById(string $type, string $id, array $paramSupps = [])
    {
        try {
            $item =  $this->doGetRequest($type . '(' . $id . ')', $paramSupps);
            return $item;
        } catch (\Exception $e) {
            throw new \Exception("No $type in the database with id equal to $id. You need to add a corelation");
        }
    }


    public function createSaleOrder(array $order)
    {
        return $this->doPostRequest(self::EP_SALES_ORDERS, $order);
    }


    public function createSaleOrderLine(string $orderId, array $orderLine)
    {
        return $this->doPostRequest(self::EP_SALES_ORDERS . "($orderId)/" . self::EP_SALES_ORDERS_LINE, $orderLine);
    }


    public function getFullSaleOrder(string $id)
    {
        return  $this->getElementById(self::EP_SALES_ORDERS, $id, ['$expand' => 'salesOrderLines,customer']);
    }

    public function getFullSaleOrderByNumber(string $number)
    {
        return $this->getElementByNumber(self::EP_SALES_ORDERS, $number, 'number', ['$expand' => 'salesOrderLines,customer']);
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



    public function getAllCustomers()
    {
        return $this->doGetRequest(self::EP_CUSTOMERS);
    }


    public function getCustomer(string $id)
    {
        return $this->getElementById(self::EP_CUSTOMERS, $id);
    }


    public function getItem(string $id)
    {
        return $this->getElementById(self::EP_ITEMS, $id);
    }


    public function getAccountByNumber(string $number)
    {
        return $this->getElementByNumber(self::EP_ACCOUNT, $number);
    }


    public function getCustomerByNumber(string $number)
    {
        return $this->getElementByNumber(self::EP_CUSTOMERS, $number);
    }


    public function getItemByNumber(string $sku)
    {
        return $this->getElementByNumber(self::EP_ITEMS, $sku);
    }

    public function getSaleInvoice(string $id)
    {
        return $this->getElementById(self::EP_SALES_INVOICES, $id);
    }


    public function getFullSaleInvoiceByNumber(string $number)
    {
        return $this->getElementByNumber(self::EP_SALES_INVOICES, $number, 'number', ['$expand' => 'salesInvoiceLines,customer']);
    }


    public function getSaleInvoiceByNumber(string $number)
    {
        return $this->getElementByNumber(self::EP_SALES_INVOICES, $number);
    }

    public function getSaleInvoiceByExternalNumber(string $number)
    {
        return $this->getElementByNumber(self::EP_SALES_INVOICES, $number, 'externalDocumentNumber');
    }

    public function getSaleInvoiceByOrderNumber(string $number)
    {
        return $this->getElementByNumber(self::EP_SALES_INVOICES, $number, 'orderNumber', ['$expand' => 'salesInvoiceLines,customer']);
    }


    public function getSaleInvoiceByExternalDocumentNumberCustomer(string $number, string $customerNumber)
    {
        $filters = "externalDocumentNumber eq '$number' and customerNumber eq '$customerNumber' ";
        return $this->getElementsByArray(self::EP_SALES_INVOICES, $filters, false, ['$expand' => 'salesInvoiceLines,customer']);
    }



    public function getElementsByArray(string $type, string $filters, bool $all = false, array $paramSupps = [])
    {
        $query = [
            '$filter' => $filters
        ];
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




    public function getContentInvoicePdf(string $id): string
    {
        $value = $this->doGetRequest(self::EP_SALES_INVOICES . "($id)/pdfDocument")["value"];
        $firstValue = reset($value);
        return $this->downloadStream($firstValue['content@odata.mediaReadLink']);
    }
}
