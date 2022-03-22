<?php

namespace App\Service\Amazon;

use App\Entity\Product;
use App\Entity\ProductCorrelation;
use App\Helper\Utils\ExchangeRateCalculator;
use App\Service\Amazon\AmzApi;
use App\Service\MailService;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;


abstract class AmzApiImport
{

    protected $mailer;

    protected $manager;

    protected $logger;

    protected $amzApi;

    public function __construct(LoggerInterface $logger, AmzApi $amzApi, ManagerRegistry $manager, MailService $mailer, ExchangeRateCalculator $exchangeRate)
    {
        $this->logger = $logger;
        $this->amzApi = $amzApi;
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->exchangeRate = $exchangeRate;
    }

    const WAITING_TIME = 20;

    public function createReportAndImport(?DateTime $dateTimeStart = null)
    {
        try {
            $this->logger->info('Report creation ' . $this->getName());
            $report = $this->createReport($dateTimeStart);
            $this->logger->info('Report processing ReportId = ' . $report->getReportId());
            for ($i = 0; $i < 30; $i++) {
                $j = ($i + 1) * self::WAITING_TIME;
                $this->logger->info("Wait  since $j seconds  reporting is done");
                sleep(self::WAITING_TIME);
                $reportState = $this->amzApi->getReport($report->getReportId());
                if ($reportState->getPayload()->getProcessingStatus() == AmzApi::STATUS_REPORT_DONE) {
                    $this->logger->info('Report processing done');
                    $datasReport = $this->amzApi->getContentReport($reportState->getPayload()->getReportDocumentId());
                    $this->importDatas($datasReport);
                    return;
                } else if ($reportState->getPayload()->getProcessingStatus() == 'FATAL') {
                    throw new Exception('Fatal error to get report ' . $this->getName());
                } else {
                    $this->logger->info('Report processing not yet');
                }
            }
            throw new Exception('Report takes too long to be processed');
        } catch (Exception $e) {
            $this->mailer->sendEmail("[REPORT AMAZON " . $this->getName() . "]", $e->getMessage(), 'stephane.lanjard@kpsport.com');
        }
    }




    abstract protected function upsertData(array $data);

    abstract protected function createReport(?DateTime $dateTimeStart = null);

    abstract protected function getLastReportContent();


    protected function getName()
    {
        return strtoupper(str_replace("App\Service\Amazon\AmzApiImport", "", get_class($this)));
    }

    public function treatLastReport()
    {
        $datasReport = $this->getLastReportContent();
        $this->importDatas($datasReport);
        return;
    }


    public function importDatas(array $datas)
    {
        $counter = 0;
        foreach ($datas as $data) {
            $dataAmz = $this->upsertData($data);
            $counter++;
            $this->logger->info('Treatment ' . $counter . ' / ' . count($datas));
            if ($counter % 20 == 0) {
                $this->manager->flush();
                $this->manager->clear();
            }
        }
        $this->manager->flush();
    }




    protected function addProductAndBrand($amz, $orderArray)
    {
        $sku = $this->getProductCorrelationSku($amz->getSku());
        $asin = $amz->getAsin();

        $product = $this->manager->getRepository(Product::class)->findOneBy([
            'asin' => $asin
        ]);
        if (!$product) {
            $product = $this->manager->getRepository(Product::class)->findOneBy([
                'sku' => $sku
            ]);
            if ($product) {
                if (!$product->getAsin()) {
                    $product->setAsin($asin);
                }
            } else {
                $this->logger->info('New product ' . $sku);
                $product = new Product();
                $product->setAsin($amz->getAsin());
                if (array_key_exists('product-name', $orderArray)) {
                    $product->setDescription($orderArray['product-name']);
                } else {
                    $product->setDescription('Unknown');
                }
                if (array_key_exists('fnsku', $orderArray)) {
                    $product->setFnsku($orderArray['fnsku']);
                }
                $product->setSku($sku);
                $this->manager->persist($product);
                $this->manager->flush();
            }
        }
        $amz->setProduct($product);
    }


    /**
     * Undocumented function
     *
     * @param string $sku
     * @return string
     */
    protected function getProductCorrelationSku(string $sku): string
    {
        $skuSanitized = strtoupper($sku);
        $productCorrelation = $this->manager->getRepository(ProductCorrelation::class)->findOneBy(['skuUsed' => $skuSanitized]);
        return $productCorrelation ? $productCorrelation->getSkuErp() : $skuSanitized;
    }
}
