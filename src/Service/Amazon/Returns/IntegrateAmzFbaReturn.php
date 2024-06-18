<?php

namespace App\Service\Amazon\Returns;

use App\BusinessCentral\Connector\KpFranceConnector;
use App\BusinessCentral\Model\SaleReturnOrder;
use App\BusinessCentral\Model\SaleReturnOrderLine;
use App\Entity\AmazonReturn;
use App\Entity\WebOrder;
use App\Helper\MailService;
use App\Helper\Traits\TraitServiceLog;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class IntegrateAmzFbaReturn
{
    use TraitServiceLog;

    protected $errors;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    protected $manager;


    public function __construct(
        private LoggerInterface $logger,
        ManagerRegistry $manager,
        private MailService $mailer,
        private KpFranceConnector $kpFranceConnector
    ) {
        
        $this->manager = $manager->getManager();
    }



    public function transformAllSaleReturns()
    {
        $amazonReturns = $this->manager->getRepository(AmazonReturn::class)
                                ->findBy(
                                    ['statusIntegration'=>AmazonReturn::STATUS_WAITING],
                                    ['returnDate' => 'ASC']
                                );
        foreach ($amazonReturns as $amazonReturn) {
            $this->logger->info('Integration sale return '.$amazonReturn);
            $integrated = $this->transformSaleReturn($amazonReturn);
            $this->manager->flush();
        }
    }





    public function transformSaleReturn(AmazonReturn $amazonReturn)
    {
        $webOrder=$this->getWebOrder($amazonReturn);
        if (!$webOrder) {
            $this->addOnlyErrorToOrderIfNotExists($amazonReturn, 'Order not found');
            return false;
        }
        
        $invoice= $this->kpFranceConnector->getFullSaleInvoiceByNumber($webOrder->getInvoiceErp());
        if (!$invoice) {
            $this->addOnlyErrorToOrderIfNotExists($amazonReturn, 'The invoice '.$webOrder->getInvoiceErp().' has not been found in ERP');
            return false;
        } else {
            $this->logger->error('Invoice '.$webOrder->getInvoiceErp().' has been found in ERP');
        }

        $saleReturnIntegrated = $this->kpFranceConnector->getSaleReturnByInvoiceAndLpn($webOrder->getInvoiceErp(), $amazonReturn->getLicensePlateNumber());
        if ($saleReturnIntegrated) {
            $this->addLogToOrder($amazonReturn, 'Sale return already integrated in Business central '. $saleReturnIntegrated['no']);
            $amazonReturn->setStatusIntegration(AmazonReturn::STATUS_STORED);
            $amazonReturn->setSaleReturnDocument($saleReturnIntegrated['no']);
            $amazonReturn->setLocationCode($saleReturnIntegrated['locationCode']);
            
            return false;
        }


        if (!$amazonReturn->getProduct()) {
            $this->addOnlyErrorToOrderIfNotExists($amazonReturn, 'Product has not been found in the system SKU '. $amazonReturn->getSku().' FNSKU '.$amazonReturn->getFnsku());
            return false;
        }


        $this->addLogToOrder($amazonReturn, 'Creation of the sale return header');
        $skuProduct= $amazonReturn->getProduct()->getSku();
        
        $saleReturn = new SaleReturnOrder();
        $saleReturn->documentType = 'Return Order';
        
        $saleReturn->correctInvoiceNo = $invoice['number'];
        $saleReturn->sellToCustomerNo = $invoice['customerNumber'];
        $saleReturn->currencyCode = strlen($invoice['currencyCode'])>0 ? $invoice['currencyCode'] : null;
        $saleReturn->externalDocumentNo = $webOrder->getExternalNumber();
        $dateAmzonReturnBc= $amazonReturn->getReturnDateFormatYmd();
        
        $saleReturn->documentDate = $dateAmzonReturnBc;
        $saleReturn->orderDate = $dateAmzonReturnBc;

        $saleReturn->packageTrackingNo = $amazonReturn->getLicensePlateNumber();
        $saleReturn->comentSat = "Reason: ".$amazonReturn->getReason() ." Fulfillment Center: ".$amazonReturn->getFulfillmentCenterId();

        $locationCode= $this->defineLocationCode($amazonReturn);


        $saleReturnLine = null;
        $item = $this->kpFranceConnector->getItemByNumber($skuProduct);
        foreach ($invoice['salesInvoiceLines'] as $saleInvoiceLine) {
            if ($item['id']==$saleInvoiceLine['itemId']) {
                $this->addLogToOrder($amazonReturn, 'Line in invoice has been found ');
                $saleReturnLine = new SaleReturnOrderLine();
                $saleReturnLine->type = 'Producto';
                $saleReturnLine->ItemNo = $skuProduct;
                $saleReturnLine->locationCode =  $locationCode;
                $saleReturnLine->quantity =  1;
                $saleReturnLine->documentType = 'Devolución';
                $saleReturnLine->returnReasonCode = 'C07';
                $saleReturnLine->lineNo = 1000;
                $saleReturnLine->unitPrice = $saleInvoiceLine['unitPrice'];
                //$saleReturnLine->applFromItemEntry = $this->getApplFromItemEntry($skuProduct, $webOrder->getExternalNumber());
            }
        }


        
        if ($saleReturnLine) {
            $this->logger->error('Line with '.$skuProduct.' was found');
        } else {
            $this->addOnlyErrorToOrderIfNotExists($amazonReturn, 'No line has been found ');
            return false;
        }
       

        $this->addLogToOrder($amazonReturn, 'Sale header creation '.json_encode($saleReturn->transformToArray()));

        $createdSaleReturnOrder = $this->kpFranceConnector->createSaleReturnOrder($saleReturn->transformToArray());

        $createdSaleReturnOrder = $this->kpFranceConnector->updateSaleReturnOrder($createdSaleReturnOrder['no'], '*', [
            'locationCode' => $locationCode,
            'pricesIncludingVAT' => true
        ]);
        

        $saleReturnLine->documentNo= $createdSaleReturnOrder['no'];
        $this->addLogToOrder($amazonReturn, 'Sale header has been created '.$createdSaleReturnOrder['no']);


        $this->addLogToOrder($amazonReturn, 'Line creation '.json_encode($saleReturnLine->transformToArray()));
        $createdSaleReturnOrderLine = $this->kpFranceConnector->createSaleReturnOrderLine($saleReturnLine->transformToArray());
        $this->addLogToOrder($amazonReturn, 'Document finalized '.json_encode($this->kpFranceConnector->getSaleReturnByNumber($createdSaleReturnOrder['no'])));
        $amazonReturn->setStatusIntegration(AmazonReturn::STATUS_STORED);
        $amazonReturn->setLocationCode($locationCode);
        $amazonReturn->setSaleReturnDocument($createdSaleReturnOrder['no']);
        

        return true;
    }


    protected function check(AmazonReturn $amazonReturn): string
    {
        if ($amazonReturn->getStatus() == 'Reimbursed') {
            return 'AMAZON';
        }

        if (in_array($amazonReturn->getDetailedDisposition(), ['SELLABLE'])) {
            return 'AMAZON';
        }
        return 'AMAZON';
        return 'DEVPROV';
    }




    protected function getApplFromItemEntry($sku, $externalDocumentNumber): ?int
    {

        $filters = "itemNumber eq '$sku' and locationCode eq 'AMAZON' and externalDocNumber eq '$externalDocumentNumber' and entrytype eq 'Sale' and Quantity lt 0";
        $itemEntries = $this->kpFranceConnector->getAllLedgerEntries($filters);
        if(count($itemEntries)>0) {
            foreach($itemEntries as $itemEntry) {
                $filters = "ItemNo eq '$sku' and applFromItemEntry eq ".$itemEntry['entryNumber'];
                $itemEntries = $this->kpFranceConnector->getAllSaleReturnOrderLines($filters);
                if(count($itemEntries)==0) {
                    return $itemEntry['entryNumber'];
                }
            }
        }
        return 0;

    }




    protected function defineLocationCode(AmazonReturn $amazonReturn): string
    {
        if ($amazonReturn->getStatus() == 'Reimbursed') {
            return 'AMAZON';
        }

        if (in_array($amazonReturn->getDetailedDisposition(), ['SELLABLE'])) {
            return 'AMAZON';
        }
        return 'AMAZON';
        //return 'DEVPROV';
    }



    protected function getWebOrder(AmazonReturn $amazonReturn): ?WebOrder
    {
        $webOrder=$this->manager->getRepository(WebOrder::class)->findOneBy([
            'externalNumber' => $amazonReturn->getOrderId()
        ]);

        if (!$webOrder) {
            $this->logger->error('No web order found with order Id '.$amazonReturn->getOrderId());
            return null;
        }
        if (!$webOrder->getInvoiceErp()) {
            $this->logger->error('Sale return is not invoiced for '.$amazonReturn->getOrderId());
            return null;
        }

        return $webOrder;
    }
}
