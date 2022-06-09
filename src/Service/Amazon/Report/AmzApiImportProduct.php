<?php

namespace App\Service\Amazon\Report;

use AmazonPHP\SellingPartner\Marketplace;
use App\Entity\Product;
use App\Entity\ProductCorrelation;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Service\Amazon\AmzApi;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\MailService;
use DateInterval;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;

class AmzApiImportProduct
{
    private $logger;

    private $amzApi;

    private $manager;

    private $mailer;

    protected $errorProducts;
    
    private $businessCentralAggregator;

    public function __construct(
        LoggerInterface $logger,
        AmzApi $amzApi,
        ManagerRegistry $manager,
        MailService $mailer,
        BusinessCentralAggregator $businessCentralAggregator
    ) {
        $this->logger = $logger;
        $this->amzApi = $amzApi;
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->businessCentralAggregator = $businessCentralAggregator;
    }

    public const WAITING_TIME = 20;


    public function updateProducts()
    {
        try {
            $this->errorProducts = [];
            $datas = $this->getContentFromReports();
            foreach ($datas as $marketplace => $dataMarketplace) {
                foreach ($dataMarketplace as $data) {
                    $this->upsertData($data);
                }
            }

            if (count($this->errorProducts) > 0) {
                $message =  implode('<br/>', $this->errorProducts);
                $this->mailer->sendEmail("[REPORT AMAZON Product ]", $message);
            }
        } catch (Exception $e) {
            $this->mailer->sendEmail("[REPORT AMAZON Product ]", $e->getMessage());
        }
    }




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
            if ($reportState->getPayload()->getProcessingStatus() == AmzApi::STATUS_REPORT_DONE) {
                $this->logger->info('Report processing done');
                return $this->amzApi->getContentReport($reportState->getPayload()->getReportDocumentId());
            } elseif (in_array($reportState->getPayload()->getProcessingStatus(), $errors)) {
                return  $this->amzApi->getContentLastReport(
                    AmzApi::TYPE_REPORT_MANAGE_INVENTORY_ARCHIVED,
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
                $this->logger->info('New product ' . $sku);
                $product = new Product();
                $product->setAsin($importOrder['asin']);
                $product->setDescription($itemBc["displayName"]);
                $product->setFnsku($importOrder['fnsku']);
                $product->setSku($sku);
                $this->manager->persist($product);
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
        return $productCorrelation ? $productCorrelation->getSkuErp() : $skuSanitized;
    }
}
