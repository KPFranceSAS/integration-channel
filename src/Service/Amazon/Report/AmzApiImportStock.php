<?php

namespace App\Service\Amazon\Report;

use App\Entity\Product;
use App\Entity\ProductCorrelation;
use App\Entity\WebOrder;
use App\Helper\Utils\ExchangeRateCalculator;
use App\Service\Amazon\AmzApi;
use App\Service\Amazon\Report\AmzApiImport;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\BusinessCentral\ProductStockFinder;
use App\Service\MailService;
use DateInterval;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;

class AmzApiImportStock extends AmzApiImport
{
    protected $productStockFinder;

    public function __construct(LoggerInterface $logger, AmzApi $amzApi, ManagerRegistry $manager, MailService $mailer, ExchangeRateCalculator $exchangeRate, BusinessCentralAggregator $businessCentralAggregator, ProductStockFinder $productStockFinder)
    {
        parent::__construct($logger, $amzApi, $manager, $mailer, $exchangeRate, $businessCentralAggregator);
        $this->productStockFinder =$productStockFinder;
    }

    protected function createReport(?DateTime $dateTimeStart = null)
    {
        if (!$dateTimeStart) {
            $dateTimeStart = new DateTime('now');
            $dateTimeStart->sub(new DateInterval('P3D'));
        }
        return $this->amzApi->createReport($dateTimeStart, AmzApi::TYPE_REPORT_INVENTORY_DATA);
    }

    protected function getLastReportContent()
    {
        return $this->amzApi->getContentLastReport(AmzApi::TYPE_REPORT_INVENTORY_DATA);
    }



    public function importDatas(array $datas)
    {
        $this->getAllProducts();
        
        foreach ($datas as $data) {
            $this->updateLevelFba($data);
        }
        $this->manager->flush();
    }


    public function getAllProducts()
    {
        $this->setZeroToStockLevel();
        $this->products= [];
        $products= $this->manager->getRepository(Product::class)->findAll();
        foreach ($products as $product) {
            $product->setBusinessCentralStock($this->productStockFinder->getRealStockProductWarehouse($product->getSku(), WebOrder::DEPOT_FBA_AMAZON));
            $product->setSoldStockNotIntegrated($this->getSoldQtyProductNotIntegrated($product));
            $product->setReturnStockNotIntegrated($this->getReturnQtyProductNotIntegrated($product));
            $this->products[$product->getSku()] = $product;
        }
    }


    public function updateLevelFba($data)
    {
        $sku = $this->getProductCorrelationSku($data['seller-sku']);
        $typeFormatted = ucfirst(strtolower($data['Warehouse-Condition-code']));
        if (array_key_exists($sku, $this->products)) {
            $product = $this->products[$sku];
            $stockFba=  $product->{'getFba'.$typeFormatted.'Stock'}() + $data['Quantity Available'];
            $product->{'setFba'.$typeFormatted.'Stock'}($stockFba);
        } else {
            $this->logger->alert('Product unknow >> '.json_encode($data));
        }
    }
    
    

    public function getSoldQtyProductNotIntegrated(Product $product)
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




    public function getReturnQtyProductNotIntegrated(Product $product)
    {
        return 0;
    }





    public function setZeroToStockLevel()
    {
        $queryBuilder = $this->manager->createQueryBuilder();
        $query = $queryBuilder->update('App\Entity\Product', 'p')
                ->set('p.fbaSellableStock', 0)
                ->set('p.fbaUnsellableStock', 0)
                ->set('p.fbaInboundStock', 0)
                ->set('p.fbaOutboundStock', 0)
                ->set('p.businessCentralStock', 0)
                ->set('p.soldStockNotIntegrated', 0)
                ->set('p.returnStockNotIntegrated', 0)
                ->set('p.differenceStock', 0)
                ->set('p.ratioStock', 0)
                ->getQuery();
        $result = $query->execute();
    }

    

    protected function getProductCorrelationSku(string $sku): string
    {
        $skuSanitized = strtoupper($sku);
        $productCorrelation = $this->manager->getRepository(ProductCorrelation::class)->findOneBy(['skuUsed' => $skuSanitized]);
        return $productCorrelation ? $productCorrelation->getSkuErp() : $skuSanitized;
    }

    protected function upsertData(array $importOrder)
    {
    }
}
