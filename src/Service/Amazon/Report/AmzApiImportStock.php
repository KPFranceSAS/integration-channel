<?php

namespace App\Service\Amazon\Report;

use AmazonPHP\SellingPartner\Marketplace;
use App\Entity\Product;
use App\Entity\ProductCorrelation;
use App\Entity\WebOrder;
use App\Service\Amazon\AmzApi;
use App\Service\BusinessCentral\ProductStockFinder;
use App\Service\MailService;
use DateInterval;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;

class AmzApiImportStock
{
    protected $mailer;

    protected $manager;

    protected $logger;

    protected $amzApi;

    protected $productStockFinder;

    public function __construct(
        LoggerInterface $logger,
        AmzApi $amzApi,
        ManagerRegistry $manager,
        MailService $mailer,
        ProductStockFinder $productStockFinder
    ) {
        $this->logger = $logger;
        $this->amzApi = $amzApi;
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->productStockFinder = $productStockFinder;
    }

    public function updateStocks()
    {
        $this->setZeroToStockLevel();
        $this->products = [];
        $products = $this->manager->getRepository(Product::class)->findAll();

        foreach ($products as $product) {
            $this->products[$product->getSku()] = $product;
            $product->setBusinessCentralStock($this->productStockFinder->getRealStockProductWarehouse(
                $product->getSku(),
                WebOrder::DEPOT_FBA_AMAZON
            ));
            $product->setLaRocaBusinessCentralStock($this->productStockFinder->getRealStockProductWarehouse(
                $product->getSku(),
                WebOrder::DEPOT_LAROCA
            ));
            $product->setSoldStockNotIntegrated($this->getSoldQtyProductNotIntegrated($product));
            $product->setReturnStockNotIntegrated($this->getReturnQtyProductNotIntegrated($product));
        }
        

        $datas = $this->getContentFromReports();


        foreach ($datas as $marketplace => $dataMarketplace) {
            foreach ($dataMarketplace as $data) {
                $sku = $this->getProductCorrelationSku($data['sku']);
       
                if (array_key_exists($sku, $this->products)) {
                    $product = $this->products[$sku];
                    $product->addFbaSellableStock($data['afn-fulfillable-quantity']);
                    $product->addFbaReservedStock($data['afn-reserved-quantity']);
                    $product->addFbaUnsellableStock($data['afn-unsellable-quantity']);
                    $product->addFbaRearchingStock($data['afn-researching-quantity']);
                    $product->addFbaInboundWorkingStock($data['afn-inbound-working-quantity']);
                    $product->addFbaInboundShippedStock($data['afn-inbound-shipped-quantity']);
                    $product->addFbaInboundReceivingStock($data['afn-inbound-receiving-quantity']);
                } else {
                    $this->logger->alert('Product unknow >> ' . json_encode($data));
                }
            }
        }
        $this->manager->flush();
    }

    public const WAITING_TIME = 20;

    protected function getContentFromReports()
    {
        $dateTimeStart = new DateTime('now');
        $dateTimeStart->sub(new DateInterval('PT6H'));
        $datas = [];

        $marketplaces = [
            Marketplace::ES()->id(),
            Marketplace::GB()->id(),
        ];
        foreach ($marketplaces as $marketplace) {
            $datasReport =  $this->getContentFromReportMarketplace($dateTimeStart, $marketplace);
            $this->logger->info("Data marketplace $marketplace >>>>" . count($datasReport));
            $datas[$marketplace] = $datasReport;
        }

        return $datas;
    }


    public function getContentFromReportMarketplace($dateTimeStart, $marketplace)
    {
        $report = $this->amzApi->createReport($dateTimeStart, AmzApi::TYPE_REPORT_MANAGE_INVENTORY, [$marketplace]);
        for ($i = 0; $i < 30; $i++) {
            $j = ($i + 1) * self::WAITING_TIME;
            $this->logger->info("Wait  since $j seconds  reporting is done");
            sleep(self::WAITING_TIME);
            $errors = [AmzApi::STATUS_REPORT_CANCELLED, AmzApi::STATUS_REPORT_FATAL];
            $reportState = $this->amzApi->getReport($report->getReportId());
            if ($reportState->getPayload()->getProcessingStatus() == AmzApi::STATUS_REPORT_DONE) {
                $this->logger->info('Report processing done');
                return $this->amzApi->getContentReport($reportState->getPayload()->getReportDocumentId());
            } elseif (in_array($reportState->getPayload()->getProcessingStatus(), $errors)) {
                return  $this->amzApi->getContentLastReport(
                    AmzApi::TYPE_REPORT_MANAGE_INVENTORY,
                    $dateTimeStart,
                    [$marketplace]
                );
                $this->logger->info('Get last');
                continue;
            } else {
                $this->logger->info('Report processing not yet');
            }
        }
        return [];
    }
  
   
    protected function getSoldQtyProductNotIntegrated(Product $product)
    {
        $qty = $this->manager->createQueryBuilder()
                ->select('SUM(amz.quantity) as qtyShipped')
                ->from('App\Entity\AmazonOrder', 'amz')
                ->where('amz.product = :product')
                ->andWhere('amz.itemStatus = :itemStatus')
                ->andWhere('amz.integrated = 0')
                ->andWhere('amz.isReturn = 0')
                ->setParameter('product', $product)
                ->setParameter('itemStatus', 'Shipped')
                ->getQuery()
                ->getSingleScalarResult();
        return $qty ?? 0;
    }


    protected function getReturnQtyProductNotIntegrated(Product $product)
    {
        return 0;
    }

    protected function setZeroToStockLevel()
    {
        $queryBuilder = $this->manager->createQueryBuilder();
        $query = $queryBuilder->update('App\Entity\Product', 'p')
                ->set('p.fbaSellableStock', 0)
                ->set('p.fbaUnsellableStock', 0)
                ->set('p.fbaUnsellableStock', 0)
                ->set('p.fbaInboundStock', 0)
                ->set('p.fbaOutboundStock', 0)
                ->set('p.fbaReservedStock', 0)
                ->set('p.fbaInboundShippedStock', 0)
                ->set('p.fbaInboundWorkingStock', 0)
                ->set('p.fbaInboundReceivingStock', 0)
                ->set('p.fbaResearchingStock', 0)
              
                ->set('p.fbaTotalStock', 0)
                ->set('p.businessCentralStock', 0)
                ->set('p.businessCentralTotalStock', 0)
                ->set('p.laRocaBusinessCentralStock', 0)
                ->set('p.soldStockNotIntegrated', 0)
                ->set('p.returnStockNotIntegrated', 0)
                ->set('p.differenceStock', 0)
                ->set('p.ratioStock', 0)
                ->getQuery()
                ->execute();
    }

    

    protected function getProductCorrelationSku(string $sku): string
    {
        $skuSanitized = strtoupper($sku);
        $productCorrelation = $this->manager
                                ->getRepository(ProductCorrelation::class)
                                ->findOneBy(['skuUsed' => $skuSanitized]);
        return $productCorrelation ? $productCorrelation->getSkuErp() : $skuSanitized;
    }
}
