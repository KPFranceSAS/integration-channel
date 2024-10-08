<?php

namespace App\BusinessCentral\Connector;

use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class BusinessCentralConnector
{
    final public const KP_FRANCE = "KP FRANCE";

    final public const GADGET_IBERIA = "GADGET IBERIA SL";

    final public const KIT_PERSONALIZACION_SPORT = "KIT PERSONALIZACION SPORT SL";

    final public const INIA = "INIA SLU";

    final public const KP_UK = "KP UK";


    final public const EP_ACCOUNT = "accounts";

    final public const EP_COMPANIES = "companies";

    final public const EP_SHIPMENT_METHODS = "shipmentMethods";

    final public const EP_PAYMENT_METHODS = "paymentMethods";

    final public const EP_PAYMENT_TERMS = "paymentTerms";

    final public const EP_CUSTOMERS = "customers";

    final public const EP_CUSTOMER_PAYMENT_JOURNALS = "customerPaymentJournals";

    final public const EP_CUSTOMER_PAYMENTS = "customerPayments";

    final public const EP_ITEMS = "items";

    final public const EP_ITEM_PRICES = "SalesPrices";

    final public const EP_ITEM_UNITOFMEASURE = "itemUnitOfMeasure";

    final public const EP_STOCK_PRODUCTS = "itemAvailabilities";

    final public const EP_SALES_ORDERS = "salesOrders";

    final public const EP_SALES_ORDERS_LINE = "salesOrderLines";

    final public const EP_STATUS_ORDERS = "statusOrders";

    final public const EP_SALES_INVOICES = "salesInvoices";

    final public const EP_SALES_INVOICES_LINES = "salesInvoiceLines";

    final public const EP_SALES_CREDITS_LINE = "salesCreditMemoLines";

    final public const EP_SALES_CREDITS = "salesCreditMemos";

    final public const EP_SALES_RETURNS_LINE = "SalesReturnLine";

    final public const EP_SALES_RETURNS = "SalesReturnHeader";

    final public const EP_PURCHASES_INVOICES_LINE = "purchaseInvoiceLines";

    final public const EP_PURCHASES_INVOICES = "purchaseInvoices";

    final public const EP_PURCHASES_ORDERS = "purchaseOrders";

    final public const EP_TRANSFER_ORDERS = "transferOrders";

    final public const EP_BUNDLE_CONTENT = "billOfMaterials";
    
    final public const EP_FEES_TAXES = "FeesAndTaxes";
    


    protected $logger;

    protected $debugger = false;

    protected $client;

    protected $companyId;


    protected $urlBase;

    /**
     * Constructor
     */
    public function __construct(
        HttpClientInterface $client,
        LoggerInterface $logger,
        string $urlBC,
        string $loginBC,
        string $passwordBC
    ) {
        $this->logger = $logger;
        $this->urlBase =  $urlBC . '/api/v1.0/';
        $this->client = $client->withOptions([
            'base_uri' =>  $this->urlBase,
            'headers'  => [
                'User-Agent'    => 'ProductOnboarding',
                'Authorization' => "Basic " . base64_encode("$loginBC:$passwordBC"),
            ],
        ]);
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
        

        $this->logger->info(json_encode($json));
        $response = $this->client->request(
            'POST',
            self::EP_COMPANIES . '(' . $this->getCompanyId() . ')/' . $endPoint,
            [
                'query' =>  $query,
                'json' => $json,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]
        );

        return json_decode($response->getContent(), true);
    }



    public function doPatchRequest(string $endPoint, string $etag, array $json, array $query = []): ?array
    {
        $this->logger->info("PATCH >>>>".json_encode($json));

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
            ]
        );

        return json_decode($response->getContent(), true);
    }


    

    public function doGetRequest(string $endPoint, array $query = [], $headers = null)
    {
        if (!$headers) {
            $headers =   [
                  
                  'Content-Type' => 'application/json'
            ];
        }
 
        $endPoint = $endPoint === self::EP_COMPANIES
            ? self::EP_COMPANIES
            : self::EP_COMPANIES . '(' . $this->getCompanyId() . ')/' . $endPoint;



        $response = $this->client->request(
            'GET',
            $endPoint,
            [
                'query' =>  $query,
                'headers' => $headers
            ]
        );
        
        return json_decode($response->getContent(), true);
    }


    public function downloadStream(string $endPoint): string
    {
        $response = $this->client->request(
            'GET',
            $endPoint,
        );
        return $response->getContent();
    }


    public function getElementsByArray(
        string $type,
        ?string $filters,
        bool $all = false,
        array $paramSupps = []
    ) : ?array {
        $query = [];
        if ($filters) {
            $query = [
                '$filter' => $filters
            ];
        }
        foreach ($paramSupps as $keyParam => $valParam) {
            $query[$keyParam] = $valParam;
        }
        


        if ($all) {
            $reponse = $this->doGetRequest($type, $query);
            $items = [];
            $continue=true;
            while ($continue) {
                $items = array_merge($items, $reponse ['value']);
                if (array_key_exists('@odata.nextLink', $reponse)) {

                    $url = str_replace($this->urlBase, '', $reponse['@odata.nextLink']);

                    $response = $this->client->request(
                        'GET',
                        $url,
                        [
                            'headers' => [
                                'Content-Type' => 'application/json'
                            ],
                        ]
                    );
                    
                    $reponse = json_decode($response->getContent(), true);
                } else {
                    $continue = false;
                }
            }
            return $items;
        } else {
            $reponse = $this->doGetRequest($type, $query);
            $items =  $reponse ['value'];
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
        } catch (Exception) {
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
            if (strtoupper((string) $company['name']) == $name) {
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



    public function getItemUnitOfMeasure($itemNo, $codeNumber='UDS')
    {
        return $this->getElementsByArray(self::EP_ITEM_UNITOFMEASURE, "ItemNo eq '$itemNo' and Code eq '$codeNumber'");
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



    public function createReservation($reservation)
    {
        return $this->doPostRequest(
            'CreateReserves',
            $reservation
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

        $this->logger->debug('Order creation '.json_encode($order));

        return $this->doPostRequest(
            self::EP_SALES_ORDERS,
            $order
        );
    }


     /**
     * Sale order
     */
    public function createPurchaseInvoice(array $order): ?array
    {
        $this->logger->debug('Purchase invoice '.json_encode($order));

        return $this->doPostRequest(
            self::EP_PURCHASES_INVOICES,
            $order
        );
    }



     /**
     * Sale order
     */

     public function updatePurchaseInvoice(string $id, string $etag, array $order): ?array
     {
         return $this->doPatchRequest(
             self::EP_PURCHASES_INVOICES . '(' . $id . ')',
             $etag,
             $order
         );
     }
 


    public function getFullPurchaseInvoice(string $id): ?array
    {
        return  $this->getElementById(
            self::EP_PURCHASES_INVOICES,
            $id,
            ['$expand' => 'purchaseInvoiceLines']
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
    * Sale order
    */

    public function updateSaleReturnOrder(string $id, string $etag, array $order): ?array
    {
        return $this->doPatchRequest(
            self::EP_SALES_RETURNS . "('" . $id . "')",
            $etag,
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


    /**
    * Sale return order
    */
    public function getAllSaleReturnOrderLines($filters): ?array
    {
        return $this->getElementsByArray(
            self::EP_SALES_RETURNS_LINE,
            $filters,
            true
        );
    }



    /**
    * Sale return order
    */
    public function getAllLedgerEntries($filters): ?array
    {
        return $this->getElementsByArray(
            'itemLedgerEntries',
            $filters,
            true
        );
    }

    public function getPricesPerGroup($group)
    {
        $filter = "SalesType eq 'Customer Price Group' and SalesCode eq '$group'";
        return $this->getElementsByArray(self::EP_ITEM_PRICES, $filter, true);
    }




    public function getSaleReturnByNumber(string $number): ?array
    {
        return $this->getSaleReturnBy("no eq '$number'");
    }

    public function getSaleReturnBy(string $condition): ?array
    {
        $return = $this->getElementsByArray(
            self::EP_SALES_RETURNS,
            "$condition",
            false,
            ['$expand' => 'customer']
        );

        if ($return) {
            $documentNo =$return['no'];
            $return['salesReturnOrderLines'] = $this->getElementsByArray(
                self::EP_SALES_RETURNS_LINE,
                "documentNo eq '$documentNo'",
                true
            );
        }
        return $return;
    }




    public function getSaleReturns(string $condition): ?array
    {
        return $this->getElementsByArray(
            self::EP_SALES_RETURNS,
            "$condition",
            true,
            ['$expand' => 'customer']
        );

    }




    public function getSaleReturnByInvoiceAndLpn($invoiceNumber, $lpn): ?array
    {
        return $this->getSaleReturnBy("correctInvoiceNo eq '$invoiceNumber' and packageTrackingNo eq '$lpn'");
    }




    public function getSaleReturnByExternalNumber(string $number): ?array
    {
        return $this->getSaleReturnBy("externalDocumentNo  eq '$number'");
    }


    public function getSaleReturnByInvoice(string $number): ?array
    {
        return $this->getSaleReturnBy("appliesToDocNo eq '$number' or correctInvoiceNo eq '$number'");
    }


    public function getSaleReturnByPackageTrackingNo(string $number): ?array
    {
        return $this->getSaleReturnBy("packageTrackingNo eq '$number'");
    }



    public function getSaleReturnByLpnAndExternalNumber(string $lpn, string $number): array
    {
        return $this->getSaleReturnBy("packageTrackingNo eq '$lpn' and externalDocumentNo eq '$number'");
    }



    public function getSaleMemos($params): array
    {
        return $this->getElementsByArray(
            'salesCreditMemos',
            null,
            true
        );
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



    public function getGeneralJournalByCode(string $code): ?array
    {
        return $this->getElementsByArray(
            'journals',
            "code eq '$code'",
        );
    }



    public function createJournalLine(string $id, array $line): ?array
    {
        return $this->doPostRequest(
            'journals(' . $id . ')/journalLines',
            $line
        );
    }
 
 
 
    public function updateJournalLine(string $idJournal, string $idJournalLine, string $etag, array $journalLine): ?array
    {
        $this->logger->info("PATCH >>>>".json_encode($journalLine));
        return $this->doPatchRequest('journals(' . $idJournal . ")/journalLines(".$idJournalLine.")", '*', $journalLine);
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
