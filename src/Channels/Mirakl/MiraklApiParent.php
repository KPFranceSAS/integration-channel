<?php

namespace App\Channels\Mirakl;

use App\Service\Aggregator\ApiInterface;
use DateInterval;
use DateTime;
use Exception;
use GuzzleHttp\Client;
use Mirakl\Core\Domain\Collection\DocumentCollection;
use Mirakl\Core\Domain\Document;
use Mirakl\Core\Domain\FileWrapper;
use Mirakl\MCI\Common\Domain\Product\ProductImportTracking;
use Mirakl\MCI\Shop\Client\ShopApiClient;
use Mirakl\MCI\Shop\Request\Product\DownloadProductImportTransformationErrorReportRequest;
use Mirakl\MCI\Shop\Request\Product\ProductImportRequest;
use Mirakl\MCI\Shop\Request\Product\ProductImportStatusesRequest;
use Mirakl\MMP\Common\Domain\Order\Accept\AcceptOrderLine;
use Mirakl\MMP\OperatorShop\Domain\Collection\DocumentRequest\UploadAccountingDocumentCollection;
use Mirakl\MMP\OperatorShop\Domain\DocumentRequest\AccountingDocumentFile;
use Mirakl\MMP\OperatorShop\Domain\DocumentRequest\UploadAccountingDocument;
use Mirakl\MMP\OperatorShop\Request\Message\GetThreadDetailsRequest;
use Mirakl\MMP\OperatorShop\Request\Message\GetThreadsRequest;
use Mirakl\MMP\Shop\Request\DocumentRequest\GetAccountingDocumentsRequest;
use Mirakl\MMP\Shop\Request\DocumentRequest\UploadAccountingDocumentsRequest;
use Mirakl\MMP\Shop\Request\Offer\GetOffersRequest;
use Mirakl\MMP\Shop\Request\Offer\Importer\OfferImportErrorReportRequest;
use Mirakl\MMP\Shop\Request\Offer\Importer\OffersImportsRequest;
use Mirakl\MMP\Shop\Request\Offer\UpdateOffersRequest;
use Mirakl\MMP\Shop\Request\Order\Accept\AcceptOrderRequest;
use Mirakl\MMP\Shop\Request\Order\Document\UploadOrdersDocumentsRequest;
use Mirakl\MMP\Shop\Request\Order\Get\GetOrdersRequest;
use Mirakl\MMP\Shop\Request\Order\Ship\ShipOrderRequest;
use Mirakl\MMP\Shop\Request\Order\Tracking\UpdateOrderTrackingInfoRequest;
use Psr\Log\LoggerInterface;
use SplFileObject;
use Symfony\Component\Filesystem\Filesystem;

abstract class MiraklApiParent implements ApiInterface
{
    protected $client;

    protected $logger;


    protected $clientUrl;


    protected $clientKey;

    protected $shopId;

    protected $projectDir;


    public function __construct(LoggerInterface $logger, string $projectDir, string $clientUrl, string $clientKey, ?string $shopId=null)
    {
        $this->client = new ShopApiClient($clientUrl, $clientKey, $shopId);
        $this->client->setLogger($logger);
        $this->projectDir =  $projectDir.'/var/invoices/';
        $this->logger = $logger;
        $this->clientUrl = $clientUrl;
        $this->clientKey = $clientKey;
        $this->shopId = $shopId;
    }

    public function getClient(): ShopApiClient
    {
        return $this->client;
    }



    public function getMessage($idMessage)
    {
        $request = new GetThreadDetailsRequest($idMessage);
        return $this->client->getThreadDetails($request);
    }


    /**
     * Summary of GetOrdersRequest
     * @return array
     */
    public function getMessages(array $params = []): array
    {
        $continue = true;
        $orders = [];
        $nextToken = null;
        $realOffset = 1;
        while ($continue) {
            $req = new GetThreadsRequest();
            foreach ($params as $key => $param) {
                $req->setData($key, $param);
            }

            $req->setMax(self::PAGINATION);
            if ($nextToken) {
                $req->setPageToken($nextToken);
            }
            
           
            $this->logger->info('Get threads batch n°' . $realOffset .  ' >>' . json_encode($params));
            $reponse = $this->client->getThreads($req);
            if (count($reponse->getCollection()->getItems()) > 0) {
                $orders = array_merge($orders, $reponse->getCollection()->getItems());
            }
            $realOffset++;
            if ($reponse->getNextPageToken()) {
                $nextToken = $reponse->getNextPageToken();
            } else {
                $continue = false;
            }
            
        }
        $ordersSanitized = [];
        foreach ($orders as $order) {
            $ordersSanitized[]=$order->toArray();
        }
        return $ordersSanitized;
    }






    /**
     * Summary of GetOrdersRequest
     * @return array
     */
    public function getAccountingDocumentRequests($type, $status, $entityType): array
    {
        $continue = true;
        $orders = [];
        $nextToken = null;
        $realOffset = 1;
        while ($continue) {
            $req = new GetAccountingDocumentsRequest();
            $req->setTypes([$type]);
            $req->setStates([$status]);
            $req->setEntityTypes([$entityType]);

            $req->setMax(self::PAGINATION);
            if ($nextToken) {
                $req->setPageToken($nextToken);
            }
            

            $this->logger->info('Get Accounting documents batch n°' . $realOffset .  ' >>' . $type.' '.$status);
            $reponse = $this->client->getAccountingDocumentsRequests($req);
            if (count($reponse->getCollection()->getItems()) > 0) {
                $orders = array_merge($orders, $reponse->getCollection()->getItems());
            }
            $realOffset++;
            if ($reponse->getNextPageToken()) {
                $nextToken = $reponse->getNextPageToken();
            } else {
                $continue = false;
            }
            
        }
        $ordersSanitized = [];
        foreach ($orders as $order) {
            $ordersSanitized[]=$order->toArray();
        }
        return $ordersSanitized;
    }



    
    /**
     * Summary of GetOrdersRequest
     * @return array
     */
    public function getOrders(array $params = []): array
    {
        $offset = 0;
        $max_page = 1;
        $orders = [];
        while ($offset  < $max_page) {
            $req = new GetOrdersRequest();
            foreach ($params as $key => $param) {
                $req->setData($key, $param);
            }

            $req->setMax(self::PAGINATION);
            $req->setOffset($offset);
            $realOffset =  $offset+1;
            $this->logger->info('Get orders batch n°' . $realOffset . ' / ' . $max_page . ' >>' . json_encode($params));
            $reponse = $this->client->getOrders($req);
            if (count($reponse->getItems()) > 0) {
                $orders = array_merge($orders, $reponse->getItems());
            }
            $offset+=self::PAGINATION;
            $max_page  = $reponse->getTotalCount();
        }
        $ordersSanitized = [];
        foreach ($orders as $order) {
            $ordersSanitized[]=$order->toArray();
        }
        return $ordersSanitized;
    }


    public function getAllOrdersToSend()
    {
        $params = [
            'order_states' => [
                "SHIPPING"
            ]
        ];
        return $this->getOrders($params);
    }




    /**
     * Summary of GetOrdersRequest
     * @return array
     */
    public function getOffers(array $params = []): array
    {
        $offset = 0;
        $max_page = 1;
        $orders = [];
        while ($offset  < $max_page) {
            $req = new GetOffersRequest($this->shopId);
            foreach ($params as $key => $param) {
                $req->setData($key, $param);
            }

            $req->setMax(self::PAGINATION);
            $req->setOffset($offset);
            $realOffset =  $offset+1;
            $this->logger->info('Get offers batch n°' . $realOffset . ' / ' . $max_page . ' >>' . json_encode($params));
            $reponse = $this->client->getOffers($req);
            if (count($reponse->getItems()) > 0) {
                $orders = array_merge($orders, $reponse->getItems());
            }
            $offset+=self::PAGINATION;
            $max_page  = $reponse->getTotalCount();
        }
        $ordersSanitized = [];
        foreach ($orders as $order) {
            $ordersSanitized[]=$order->toArray();
        }
        return $ordersSanitized;
    }





    public function getAllOrdersToAccept()
    {
        $params = [
            'order_states' => [
                'WAITING_ACCEPTANCE',
            ]
        ];
        return $this->getOrders($params);
    }



    

   
    public function getOrder(string $orderNumber)
    {
        $this->logger->info('Get Order  ' . $orderNumber);
        return;
    }

    

    public function sendOfferImports(array  $offers)
    {
        $request = new UpdateOffersRequest();
        $request->setOffers($offers);
        $result = $this->client->updateOffers($request);
        return $result;
    }


    public function sendProductImports(string $file, $operatorFormat=false): ProductImportTracking
    {
        $request = new ProductImportRequest(new SplFileObject($file));
        $request->setOperatorFormat(false);
        $result = $this->client->importProducts($request);
        return $result;
    }


    public const PAGINATION = 100;

    


    public function sendInvoice($orderId, $invoiceNumber, $invoiceContent)
    {
        $docs = new DocumentCollection();
        $fs = new Filesystem();
        $filename= 'invoice_'.str_replace("/", '_', (string) $invoiceNumber).'_'.date('YmdHis').'.pdf';
        $filePath = $this->projectDir.$filename;
        $fs->dumpFile($filePath, $invoiceContent);
        $file = new SplFileObject($filePath);

        $docs->add(new Document($file, $filename, 'CUSTOMER_INVOICE'));
        $request = new UploadOrdersDocumentsRequest($docs, $orderId);
        $result = $this->client->uploadOrderDocuments($request);
        $fs->remove($filePath);
        
        return true;
    }



    public function uploadAccountingDocument($request, $invoice, $invoiceContent)
    {
        $dateInvoice = DateTime::createFromFormat('Y-m-d', $invoice["invoiceDate"]);
        
        $fs = new Filesystem();
        $filename= 'invoice_'.str_replace("/", '_', (string) $invoice['number']).'_'.date('YmdHis').'.pdf';
        $filePath = $this->projectDir.$filename;
        $fs->dumpFile($filePath, $invoiceContent);

        $fileStr = new AccountingDocumentFile();
        $fileStr->setName($filename);
        $fileStr->setFormat('PDF');


        $file = new FileWrapper(new SplFileObject($filePath));
        $docs = new UploadAccountingDocumentCollection();
        $doc = new UploadAccountingDocument();
        $doc->setDocumentNumber($invoice['number']);
        $doc->setRequestId($request['id']);
        $doc->setIssueDate($dateInvoice);
        $doc->setDueDate($dateInvoice);
        $doc->setTotalAmountExcludingTaxes($request['total_amount_excluding_taxes']);
        $doc->setTotalTaxAmount($request['total_tax_amount']);
        $doc->setFiles([$fileStr]);
        
        $docs->add($doc);

        $request = new UploadAccountingDocumentsRequest($docs);
        $request->addFile($file);
        
        $result = $this->client->uploadAccountingDocuments($request);
        $fs->remove($filePath);
        
        return true;
    }


   
   

    





    public function markOrderAsFulfill($orderId, $carrierCode, $carrierName, $carrierUrl, $trackingNumber):bool
    {

        $params = [
            'carrier_name' => $carrierName,
            'carrier_url' => $carrierUrl,
            'tracking_number' => $trackingNumber,
        ];

        if ($carrierCode) {
            $params['carrier_code'] = $carrierCode;
        }


        $request = new UpdateOrderTrackingInfoRequest($orderId, $params);
        $result = $this->client->updateOrderTrackingInfo($request);


        $request = new ShipOrderRequest($orderId);
        $result = $this->client->shipOrder($request);
        return true;
    }



    public function markOrderAsAcceptedRefused($order, $accepted): bool
    {
        $ordersId = [];
        foreach ($order['order_lines'] as $orderLine) {
            if ($orderLine["status"]['state']=='WAITING_ACCEPTANCE') {
                $ordersId[] =  new AcceptOrderLine(['id' => $orderLine['id'], 'accepted' => $accepted]);
            }
        }
        if (count($ordersId)>0) {
            $request = new AcceptOrderRequest($order['id'], $ordersId);
            $this->client->acceptOrder($request);
            return true;
        } else {
            return false;
        }
    }


    public function markOrderAsAccepted($order): bool
    {
        return $this->markOrderAsAcceptedRefused($order, true);
    }


    public function markOrderAsRefused($order): bool
    {
        return $this->markOrderAsAcceptedRefused($order, false);
    }
        


    public function getAllAttributesForCategory($hierarchyCode)
    {
        $params = [
            'hierarchy' => $hierarchyCode,
            'max_level' => 0,
            'all_operator_attributes' => "true"
        ];
        return $this->sendRequest('products/attributes', $params);
    }


   


    /**

    */
    public function getReportErrorOffer($id):array
    {
        $request = new OfferImportErrorReportRequest($id);
        $result = $this->client->getOffersImportErrorReport($request);
        $file = $result->getFile();
        $errors = [];
        $header = null;
        while (!$file->eof()) {
            if (!$header) {
                $header=$file->fgetcsv();
            } else {
                $errors[]=array_combine($header, $file->fgetcsv());
            }
        }
        return $errors;
    }





    /**

    */
    public function getReportErrorProduct($id):array
    {
        $request = new DownloadProductImportTransformationErrorReportRequest($id);
        $result = $this->client->downloadProductImportTransformationErrorReport($request);
        return $this->transformResultFileInArray($result);
    }




    public function transformResultFileInArray(FileWrapper $fielWrapper)
    {
        $file = $fielWrapper->getFile();
        $contents = [];
        $header = null;
        while (!$file->eof()) {
            if (!$header) {
                $header=$file->fgetcsv();
            } else {
                $contents[]=array_combine($header, $file->fgetcsv());
            }
        }
        return $contents;
    }



    /**
        * @return Mirakl\MMP\OperatorShop\Domain\Offer\Importer\OfferImport[]
        */
    public function getLastOfferImports():array
    {
        $request = new OffersImportsRequest();
        $request->setStatus('COMPLETE');
        return $this->client->getOffersImports($request)->getCollection()->getItems();
    }

    public function getLastProductImports():array
    {
        $request = new ProductImportStatusesRequest();
        $now = new DateTime();
        $now->sub(new DateInterval('PT12H'));
        $request->setLastRequestDate($now);
        return $this->client->getProductImportStatuses($request)->getItems();
    }


    public function getLastProductImport()
    {
        $lastImports = $this->getLastProductImports();
        $toReturn = null;
        foreach ($lastImports as $lastImport) {
            if (!$toReturn || ($toReturn->getDateCreated()<$lastImport->getDateCreated())) {
                $toReturn=$lastImport;
            }
        }
        return $toReturn;


    }



    



    public function getAllAttributes()
    {
        return $this->sendRequest('products/attributes', [
            'all_operator_attributes' => "true"
        ]);
    }



    public function getAllAttributesValueForCode($code)
    {
        $params = [
            'code' => $code,
        ];
        return $this->sendRequest('values_lists', $params);
    }


    public function getAllAttributesValue()
    {
        return $this->sendRequest('values_lists');
    }



    public function getCategorieChoices()
    {
        $categoryIndexed = [];
        $parentCode= [];

        $categories = $this->sendRequest('hierarchies');
        foreach ($categories->hierarchies as $hierarchy) {
            $categoryIndexed[$hierarchy->code] = $hierarchy;
            if (strlen($hierarchy->parent_code)>0) {
                $parentCode[]=$hierarchy->parent_code;
            }
        }

        
        $finalCategories = [];
        foreach ($categories->hierarchies as $hierarchy) {
            if (!in_array($hierarchy->code, $parentCode)) {
                $this->logger->info("LAst level ".$hierarchy->code);
                $categorie = [
                    'code' => $hierarchy->code,
                    'label' => $hierarchy->label,
                ];

                $path = [];
                
                $categoryCheck = $hierarchy;
                while ($categoryCheck) {
                    $this->logger->info("Add path ".$categoryCheck->label);
                    $path[] = $categoryCheck->label;
                    if (strlen($categoryCheck->parent_code)>0) {
                        $categoryCheck = $categoryIndexed[$categoryCheck->parent_code] ;
                    } else {
                        $categoryCheck=false;
                    }
                }
                $pathArray = array_reverse($path);
                $categorie ['path'] = implode(' > ', $pathArray);
                $finalCategories[ $categorie['path'].' - '.$categorie['code']] = $categorie;
                $this->logger->info("finish ".$categorie['path']);
            }
           
        }

        ksort($finalCategories);

        return $finalCategories;
    }





    public function sendRequest($endPoint, $queryParams = [], $method = 'GET', $form = null)
    {
        $parameters = [
            'query' => $queryParams,
            'debug' => false,
            'headers' => [
                    "Authorization"=>$this->clientKey
                    ]
        ];
        if ('GET' != $method && $form) {
            $parameters['json'] = $form;
        }
        $client = new Client();
        $response = $client->request($method, $this->clientUrl."/". $endPoint, $parameters);

        return json_decode($response->getBody());
    }
}
