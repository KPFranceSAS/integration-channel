<?php

namespace App\Service\Amazon\Report;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\Entity\Product;
use App\Entity\ProductCorrelation;
use App\Helper\Utils\ExchangeRateCalculator;
use App\Service\Amazon\AmzApi;
use App\Helper\MailService;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;

abstract class AmzApiImport
{
    protected $mailer;

    protected $manager;

    protected $logger;

    protected $errors;

    protected $amzApi;

    protected $exchangeRate;

    protected $businessCentralAggregator;

    public function __construct(
        LoggerInterface $logger,
        AmzApi $amzApi,
        ManagerRegistry $manager,
        MailService $mailer,
        ExchangeRateCalculator $exchangeRate,
        BusinessCentralAggregator $businessCentralAggregator
    ) {
        $this->logger = $logger;
        $this->amzApi = $amzApi;
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->exchangeRate = $exchangeRate;
        $this->businessCentralAggregator = $businessCentralAggregator;
    }

    public const WAITING_TIME = 20;

    public function createReportAndImport(?DateTime $dateTimeStart = null)
    {
        try {
            $badStatus = [AmzApi::STATUS_REPORT_CANCELLED, AmzApi::STATUS_REPORT_FATAL];
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
                } elseif (in_array($reportState->getPayload()->getProcessingStatus(), $badStatus)) {
                    throw new Exception('Fatal error to get report ' . $this->getName());
                } else {
                    $this->logger->info('Report processing not yet');
                }
            }
            throw new Exception('Report takes too long to be processed');
        } catch (Exception $e) {
            $this->mailer->sendEmail("[REPORT AMAZON " . $this->getName() . "]", $e->getMessage());
        }
    }




    abstract protected function upsertData(array $data);

    abstract protected function createReport(?DateTime $dateTimeStart = null);

    abstract protected function getLastReportContent();


    protected function getName()
    {
        return strtoupper(str_replace("App\Service\Amazon\Report\AmzApiImport", "", get_class($this)));
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
        $this->errors = [];
        foreach ($datas as $data) {
            try {
                $dataAmz = $this->upsertData($data);
                $counter++;
                $this->logger->info('Treatment ' . $counter . ' / ' . count($datas));
                if ($counter % 20 == 0) {
                    $this->manager->flush();
                    $this->manager->clear();
                }
            } catch (\Exception $e) {
                $this->logger->alert($e->getMessage());
                $this->errors[] = $e->getMessage();
            }
        }
        $this->manager->flush();

        if (count($this->errors) > 0) {
            throw new Exception(implode('<br/>', $this->errors));
        }
    }




    protected function addProductByAsin($amz)
    {
        $sku = $this->getProductCorrelationSku($amz->getSku());
        $asin = $amz->getAsin();

        $product = $this->manager->getRepository(Product::class)->findOneBy([
            'asin' => $asin,
            "sku" => $sku
        ]);
        if ($product) {
            $amz->setProduct($product);
        }
    }


    protected function addProductByFnsku($amz)
    {
        $fnsku = $amz->getFnsku();
        $params = [
            'fnsku' => $fnsku
        ];

        if ($amz->getSku()) {
            $sku = $this->getProductCorrelationSku($amz->getSku());
            $params ['sku'] = $sku;
        }

        $product = $this->manager
                        ->getRepository(Product::class)
                        ->findOneBy($params);
        if ($product) {
            $amz->setProduct($product);
        }
    }


    protected function getProductCorrelationSku(string $sku): string
    {
        $skuSanitized = strtoupper($sku);
        $productCorrelation = $this->manager
                                    ->getRepository(ProductCorrelation::class)
                                    ->findOneBy([
                                        'skuUsed' => $skuSanitized
                                    ]);
        return $productCorrelation ? $productCorrelation->getSkuErpBc() : $skuSanitized;
    }
}
