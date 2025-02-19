<?php

namespace App\Service\Amazon\Report;

use AmazonPHP\SellingPartner\Marketplace;
use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Entity\Product;
use App\Entity\ProductCorrelation;
use App\Entity\ProductSaleChannel;
use App\Entity\SaleChannel;
use App\Helper\MailService;
use App\Service\Amazon\AmzApi;
use DateInterval;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;

class AmzApiImportProduct
{
    private $manager;

    protected $errorProducts;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AmzApi $amzApi,
        ManagerRegistry $manager,
        private readonly MailService $mailer,
        private readonly BusinessCentralAggregator $businessCentralAggregator
    ) {
        $this->manager = $manager->getManager();
    }

    final public const WAITING_TIME = 60;


    public function updateProducts()
    {
        try {
            $this->getNewProductsFromAmazon();
            $this->addUnitCosts();
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
            //$this->mailer->sendEmail("[REPORT AMAZON Product ]", $e->getMessage());
        }
    }



    public function getNewProductsFromAmazon()
    {
        $this->errorProducts = [];
        $datas = $this->getContentFromReports();
        foreach ($datas as $marketplace => $dataMarketplace) {
            foreach ($dataMarketplace as $data) {
                $this->upsertData($data);
            }
        }

        if (count($this->errorProducts) > 0) {
            $message =  implode('<br/>', $this->errorProducts);
            $this->logger->critical($message);
            //$this->mailer->sendEmail("[REPORT AMAZON Product ]", $message);
        }
    }


    public function addUnitCosts()
    {
        $connector = $this->businessCentralAggregator->getBusinessCentralConnector(BusinessCentralConnector::KP_FRANCE);
        $items = $this->manager->getRepository(Product::class)->findAll();
            
        foreach ($items as $item) {
            $itemBc = $connector->getItemByNumber($item->getSku());
            if ($itemBc) {
                $this->logger->info('Product price for '.$item->getSku().' is '.$itemBc['unitCost']);
                $item->setUnitCost($itemBc['unitCost']);
            } else {
                $this->logger->alert('Product price for '.$item->getSku().'not found');
            }
        }


        $this->manager->flush();
    }
    


    protected function getContentFromReports()
    {
        $dateTimeStart = new DateTime('now');
        $dateTimeStart->sub(new DateInterval('PT6H'));
        $datas = [];

        $marketplaces = [
            Marketplace::fromCountry('ES')->id(),
            Marketplace::fromCountry('FR')->id(),
            Marketplace::fromCountry('DE')->id(),
            Marketplace::fromCountry('IT')->id(),
            Marketplace::fromCountry('GB')->id(),
        ];
        foreach ($marketplaces as $marketplace) {
            $datasReport =  $this->getContentFromReportMarketplace($dateTimeStart, $marketplace);
            if(is_array($datasReport)) {
                $this->logger->info("Data marketplace $marketplace >>>>" . count($datasReport));
                $datas[$marketplace] = $datasReport;
          
            }
            sleep(self::WAITING_TIME);
        }

        return $datas;
    }


    public function getContentFromReportMarketplace($dateTimeStart, $marketplace)
    {
        try {
            $report = $this->amzApi->createReport(
                $dateTimeStart,
                AmzApi::TYPE_REPORT_MANAGE_INVENTORY_ARCHIVED,
                [$marketplace]
            );
        
       
            for ($i = 0; $i < 30; $i++) {
                $j = ($i + 1) * self::WAITING_TIME;
                $this->logger->info("Wait  since $j seconds  reporting is done");
                sleep(self::WAITING_TIME);
                $errors = [AmzApi::STATUS_REPORT_CANCELLED, AmzApi::STATUS_REPORT_FATAL];
                $reportState = $this->amzApi->getReport($report->getReportId());
                if ($reportState->getProcessingStatus() == AmzApi::STATUS_REPORT_DONE) {
                    $this->logger->info('Report processing done');
                    return $this->amzApi->getContentReport($reportState->getReportDocumentId());
                } elseif (in_array($reportState->getProcessingStatus(), $errors)) {
                    return  $this->amzApi->getContentLastReport(
                        AmzApi::TYPE_REPORT_MANAGE_INVENTORY_ARCHIVED,
                        $dateTimeStart,
                        [$marketplace]
                    );
                } else {
                    $this->logger->info('Report processing not yet');
                }
            }
            return [];
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
            return  $this->amzApi->getContentLastReport(
                AmzApi::TYPE_REPORT_MANAGE_INVENTORY_ARCHIVED,
                $dateTimeStart,
                [$marketplace]
            );
        }
    }




    protected function upsertData(array $importOrder)
    {
        $product = $this->manager->getRepository(Product::class)->findOneBy([
            'fnsku' => $importOrder['fnsku']
        ]);

        if ($product) {
            return $product;
        }

        $sku = $this->getProductCorrelationSku($importOrder['sku']);
        $product = $this->manager->getRepository(Product::class)->findOneBy([
            'sku' => $sku
        ]);

        if ($product) {
            $product->setAsin($importOrder['asin']);
            $product->setFnsku($importOrder['fnsku']);
            return $product;
        } else {
            $connector = $this->businessCentralAggregator->getBusinessCentralConnector(BusinessCentralConnector::KP_FRANCE);
            $itemBc = $connector->getItemByNumber($sku);
            if ($itemBc) {
                $saleChannels = $this->manager->getRepository(SaleChannel::class)->findAll();
                $this->logger->info('New product ' . $sku);
                $product = new Product();
                $product->setAsin($importOrder['asin']);
                $product->setDescription($itemBc["displayName"]);
                $product->setFnsku($importOrder['fnsku']);
                $product->setSku($sku);
                $this->manager->persist($product);
                $this->manager->flush();
                foreach ($saleChannels as $saleChannel) {
                    $productSaleChannel = new ProductSaleChannel();
                    $productSaleChannel->setProduct($product);
                    $saleChannel->addProductSaleChannel($productSaleChannel);
                }
                $this->manager->flush();
            } else {
                $this->errorProducts[] = 'Product ' . $sku . ' not found in Business central';
            }
        }
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
