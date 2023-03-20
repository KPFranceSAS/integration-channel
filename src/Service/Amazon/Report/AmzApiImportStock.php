<?php

namespace App\Service\Amazon\Report;

use AmazonPHP\SellingPartner\Marketplace;
use App\BusinessCentral\ProductStockFinder;
use App\Entity\Product;
use App\Entity\ProductCorrelation;
use App\Entity\WebOrder;
use App\Helper\MailService;
use App\Service\Amazon\AmzApi;
use DateInterval;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class AmzApiImportStock
{
    protected $mailer;

    protected $manager;

    protected $logger;

    protected $amzApi;

    protected $productStockFinder;

    protected $products;

    public function __construct(
        LoggerInterface $logger,
        AmzApi $amzApi,
        ManagerRegistry $manager,
        MailService $mailer,
        ProductStockFinder $productStockFinder
    ) {
        $this->logger = $logger;
        $this->amzApi = $amzApi;
        /** @var \Doctrine\ORM\EntityManagerInterface */
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->productStockFinder = $productStockFinder;
    }

    public function updateStocks()
    {
        $datas = $this->getContentFromReports();


        $this->setZeroToStockLevel();
        $this->products = [];
        /** @var array[\App\Entity\Product] */
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

        $importSku = [];


        foreach ($datas as $dataMarketplace) {
            $codeMarketplace = $dataMarketplace['code'];
            foreach ($dataMarketplace["data"] as $data) {
                $sku = $this->getProductCorrelationSku($data['sku']);
                $slug = $sku . '-' . $codeMarketplace;
                if (!in_array($slug, $importSku)) {
                    if (array_key_exists($sku, $this->products)) {
                        $product = $this->products[$sku];
                        $product->addFbaSellableStock($data['afn-fulfillable-quantity'], $codeMarketplace);
                        $product->addFbaReservedStock($data['afn-reserved-quantity'], $codeMarketplace);
                        $product->addFbaUnsellableStock($data['afn-unsellable-quantity'], $codeMarketplace);
                        $product->addFbaRearchingStock($data['afn-researching-quantity'], $codeMarketplace);
                        $product->addFbaInboundWorkingStock($data['afn-inbound-working-quantity'], $codeMarketplace);
                        $product->addFbaInboundShippedStock($data['afn-inbound-shipped-quantity'], $codeMarketplace);
                        $product->addFbaInboundReceivingStock($data['afn-inbound-receiving-quantity'], $codeMarketplace);
                    } else {
                        $this->logger->alert('Product unknow >> ' . json_encode($data));
                    }
                    $importSku[] = $slug;
                }
            }
        }
        $this->manager->flush();
    }

    public const WAITING_TIME = 10;

    protected function getContentFromReports()
    {
        $dateTimeStart = new DateTime('now');
        $dateTimeStart->sub(new DateInterval('PT6H'));
        $datas = [];

        $marketplaces = [
            ['marketplace' => Marketplace::fromCountry('ES')->id(), 'code' => 'eu'],
            ['marketplace' => Marketplace::fromCountry('FR')->id(), 'code' => 'eu'],
            ['marketplace' => Marketplace::fromCountry('DE')->id(), 'code' => 'eu'],
            ['marketplace' => Marketplace::fromCountry('IT')->id(), 'code' => 'eu'],
            ['marketplace' => Marketplace::fromCountry('GB')->id(), 'code' => 'uk'],
        ];
        foreach ($marketplaces as $marketplace) {
            $datasReport =  $this->getContentFromReportMarketplace($dateTimeStart, $marketplace['marketplace']);
            $this->logger->info("Data marketplace >>>>" . count($datasReport));
            $datas[$marketplace['marketplace']] = ['data' => $datasReport, 'code' => $marketplace['code']];
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

            ->set('p.fbaUkSellableStock', 0)
            ->set('p.fbaUkUnsellableStock', 0)
            ->set('p.fbaUkUnsellableStock', 0)
            ->set('p.fbaUkInboundStock', 0)
            ->set('p.fbaUkOutboundStock', 0)
            ->set('p.fbaUkReservedStock', 0)
            ->set('p.fbaUkInboundShippedStock', 0)
            ->set('p.fbaUkInboundWorkingStock', 0)
            ->set('p.fbaUkInboundReceivingStock', 0)
            ->set('p.fbaUkResearchingStock', 0)
            ->set('p.fbaUkTotalStock', 0)

            ->set('p.fbaEuSellableStock', 0)
            ->set('p.fbaEuUnsellableStock', 0)
            ->set('p.fbaEuUnsellableStock', 0)
            ->set('p.fbaEuInboundStock', 0)
            ->set('p.fbaEuOutboundStock', 0)
            ->set('p.fbaEuReservedStock', 0)
            ->set('p.fbaEuInboundShippedStock', 0)
            ->set('p.fbaEuInboundWorkingStock', 0)
            ->set('p.fbaEuInboundReceivingStock', 0)
            ->set('p.fbaEuResearchingStock', 0)
            ->set('p.fbaEuTotalStock', 0)

            ->set('p.businessCentralStock', 0)
            ->set('p.businessCentralTotalStock', 0)
            ->set('p.laRocaBusinessCentralStock', 0)
            ->set('p.laRocaPurchaseBusinessCentralStock', 0)
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
        return $productCorrelation ? $productCorrelation->getSkuErpBc() : $skuSanitized;
    }
}
