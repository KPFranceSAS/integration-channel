<?php

namespace App\Service\Amazon\Returns;

use App\BusinessCentral\Connector\KpFranceConnector;
use App\BusinessCentral\Model\SaleReturnOrder;
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




    public function transformSaleReturn(AmazonReturn $amazonReturn){
                
        $webOrder=$this->getWebOrder($amazonReturn);
        if(!$webOrder){
            return false;
        }
        
        $invoice= $this->kpFranceConnector->getFullSaleInvoiceByNumber($webOrder->getInvoiceErp());
        if(!$invoice){
            $this->logger->error('The invoice '.$webOrder->getInvoiceErp().' has not been found in ERP');
            return false;
        }

        $saleReturnIntegrated = $this->kpFranceConnector->getFullSaleInvoiceByNumber($webOrder->getInvoiceErp());

        
        $saleReturn = new SaleReturnOrder();
        $saleReturn->correctedInvoiceNo = $invoice['number'];
        $saleReturn->sellToCustomerNo = $invoice['customerNumber'];
        $saleReturn->billToCustomerNo = $invoice['billToCustomerNo'];
        $saleReturn->externalDocumentNo = $webOrder->getExternalNumber();
        $dateAmzonReturnBc= $amazonReturn->getReturnDateFormatYmd();
        $saleReturn->shipmentDate = $dateAmzonReturnBc;
        $saleReturn->documentDate = $dateAmzonReturnBc;
        $saleReturn->orderDate = $dateAmzonReturnBc;

        $saleReturn->packageTrackingNo = $amazonReturn->getLicensePlateNumber();



    }




    protected function getWebOrder(AmazonReturn $amazonReturn): ?WebOrder
    {

        $webOrder=$this->manager->getRepository(WebOrder::class)->findOneBy([
            'externalNumber' => $amazonReturn->getOrderId()
        ]);

        if(!$webOrder){
            $this->logger->error('No web order found with order Id '.$amazonReturn->getOrderId());
            return null;
        }


        if(!$webOrder->getInvoiceErp()){
            $this->logger->error('Sale return is not invoiced for '.$amazonReturn->getOrderId());
            return null;
        }

        return $webOrder;
    }



   
}
