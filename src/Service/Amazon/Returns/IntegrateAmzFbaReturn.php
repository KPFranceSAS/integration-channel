<?php

namespace App\Service\Amazon\Returns;

use App\BusinessCentral\Connector\KpFranceConnector;
use App\BusinessCentral\Model\SaleOrder;
use App\BusinessCentral\Model\SaleOrderLine;
use App\BusinessCentral\Model\SaleReturnOrder;
use App\BusinessCentral\Model\SaleReturnOrderLine;
use App\Entity\AmazonFinancialEvent;
use App\Entity\AmazonOrder;
use App\Entity\AmazonReturn;
use App\Entity\FbaReturn;
use App\Entity\WebOrder;
use App\Helper\MailService;
use App\Helper\Utils\DatetimeUtils;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class IntegrateAmzFbaReturn
{
    protected $mailer;

    protected $errors;

    protected $manager;

    protected $logger;

    protected $kpFranceConnector;

    public function __construct(
        LoggerInterface $logger,
        ManagerRegistry $manager,
        MailService $mailer,
        KpFranceConnector $kpFranceConnector
    ) {
        $this->logger = $logger;
        /** @var \Doctrine\ORM\EntityManagerInterface */
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->kpFranceConnector = $kpFranceConnector;
    }



    public function transformAllSaleReturns()
    {
        $amazonReturns = $this->manager->getRepository(AmazonReturn::class)->findAll();
        foreach ($amazonReturns as $amazonReturn) {
            $this->logger->info('Integration sale return '.$amazonReturn);
            $integrated = $this->transformSaleReturn($amazonReturn);
            if ($integrated) {
                return;
            }
        }
    }





    public function transformSaleReturn(AmazonReturn $amazonReturn)
    {
        $webOrder=$this->getWebOrder($amazonReturn);
        if (!$webOrder) {
            return false;
        }
        
        $invoice= $this->kpFranceConnector->getFullSaleInvoiceByNumber($webOrder->getInvoiceErp());
        if (!$invoice) {
            $this->logger->error('The invoice '.$webOrder->getInvoiceErp().' has not been found in ERP');
            return false;
        }

        $saleReturnIntegrated = $this->kpFranceConnector->getSaleReturnByInvoiceAndLpn($webOrder->getInvoiceErp(), $amazonReturn->getLicensePlateNumber());
        if ($saleReturnIntegrated) {
            $this->logger->error('Sale return already integrated in Business central '. $saleReturnIntegrated['number']);
            return false;
        }


        if (!$amazonReturn->getProduct()) {
            $this->logger->error('Product has not been found in the system SKU '. $amazonReturn->getSku().' FNSKU '.$amazonReturn->getFnsku());
            return false;
        }

        $skuProduct= $amazonReturn->getProduct()->getSku();
        
        $saleReturn = new SaleReturnOrder();
        $saleReturn->documentType = 'Return Order';
        $saleReturn->correctedInvoiceNo = $invoice['number'];
        $saleReturn->sellToCustomerNo = $invoice['customerNumber'];
        $saleReturn->billToCustomerNo = $invoice['customerNumber'];
        $saleReturn->externalDocumentNo = $webOrder->getExternalNumber();
        $dateAmzonReturnBc= $amazonReturn->getReturnDateFormatYmd();
        $saleReturn->shipmentDate = $dateAmzonReturnBc;
        $saleReturn->documentDate = $dateAmzonReturnBc;
        $saleReturn->orderDate = $dateAmzonReturnBc;

        $saleReturn->packageTrackingNo = $amazonReturn->getLicensePlateNumber();
        $saleReturn->comentSAT = "Reason: ".$amazonReturn->getReason() ." Fulfillment Center: ".$amazonReturn->getFulfillmentCenterId();


        $locationCode= $this->defineLocationCode($amazonReturn);


        $saleReturnLine = null;
        $item = $this->kpFranceConnector->getItemByNumber($skuProduct);
        foreach ($invoice['salesInvoiceLines'] as $saleInvoiceLine) {
            if ($item['id']==$saleInvoiceLine['itemId']) {
                $saleReturnLine = new SaleReturnOrderLine();
                $saleReturnLine->type = SaleOrderLine::TYPE_ITEM;
                $saleReturnLine->number = $skuProduct;
                $saleReturnLine->locationCode =  $locationCode;
                $saleReturnLine->quantity =  1;
                $saleReturnLine->documentType = 'Return Order';
                $saleReturnLine->returnReasonCode = 'C07';
                $saleReturnLine->applFromItemEntry = 0;
            }
        }


        
        if ($saleReturnLine) {
            $this->logger->error('Line with '.$skuProduct.' was not found');
            // return false;
        }
       

        dump(json_encode($saleReturn->transformToArray()));
        $createdSaleReturnOrder = $this->kpFranceConnector->createSaleReturnOrder($saleReturn->transformToArray());
        dump($createdSaleReturnOrder);

        $saleReturnLine->number= $createdSaleReturnOrder['number'];

        dump(json_encode($saleReturnLine->transformToArray()));

        $createdSaleReturnOrderLine = $this->kpFranceConnector->createSaleReturnOrder($saleReturnLine->transformToArray());
        dump($createdSaleReturnOrderLine);
        dump($this->kpFranceConnector->getSaleReturnByNumber($createdSaleReturnOrder['number']));


        return true;
    }





    protected function defineLocationCode(AmazonReturn $amazonReturn): string
    {
        if ($amazonReturn->getStatus() == 'Reimbursed') {
            return 'AMAZON';
        }

        if (in_array($amazonReturn->getDetailedDisposition(), ['SELLABLE'])) {
            return 'AMAZON';
        }

        return 'DEVPROV';
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
