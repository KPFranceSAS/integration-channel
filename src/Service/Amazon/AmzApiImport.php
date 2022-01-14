<?php

namespace App\Service\Amazon;

use App\Entity\AmazonOrder;
use App\Entity\Brand;
use App\Entity\Product;
use App\Entity\ProductCorrelation;
use App\Helper\Utils\ExchangeRateCalculator;
use App\Service\Amazon\AmzApi;
use App\Service\MailService;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;


class AmzApiImport
{

    private $mailer;

    private $manager;

    private $logger;

    private $amzApi;

    public function __construct(LoggerInterface $logger, AmzApi $amzApi, ManagerRegistry $manager, MailService $mailer, ExchangeRateCalculator $exchangeRate)
    {
        $this->logger = $logger;
        $this->amzApi = $amzApi;
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->exchangeRate = $exchangeRate;
    }



    public function createReportOrdersAndImport(?DateTime $dateTimeStart = null)
    {
        try {
            $report = $this->amzApi->createReportOrdersByLastUpdate($dateTimeStart);
            $this->logger->info('Report processing ReportId = ' . $report->getReportId());
            for ($i = 0; $i < 20; $i++) {
                $this->logger->info('Wait 10s reporting is done');
                sleep(10);
                $reportState = $this->amzApi->getReport($report->getReportId());
                if ($reportState->getPayload()->getProcessingStatus() == AmzApi::STATUS_REPORT_DONE) {
                    $this->logger->info('Report processing done');
                    $ordersReport = $this->amzApi->getContentReport($reportState->getPayload()->getReportDocumentId());
                    $this->importOrders($ordersReport);
                    return;
                } else {
                    $this->logger->info('Report processing not yet');
                }
            }
            throw new Exception('Report takes too long to be processed');
        } catch (Exception $e) {
            $this->mailer->sendEmail("[AMAZ ORDERS]", $e->getMessage());
        }
    }



    public function treatLastReport()
    {
        $ordersReport = $this->amzApi->getContentLastReportOrdersByLastUpdate();
        $this->importOrders($ordersReport);
        return;
    }


    public function importOrders(array $orders)
    {
        $counter = 0;
        foreach ($orders as $order) {
            $orderAmz = $this->upsertOrder($order);
            $counter++;
            $this->logger->info('Treatment ' . $counter . ' / ' . count($orders));
            if ($counter % 50 == 0) {
                $this->manager->flush();
                $this->manager->clear();
            }
        }
        $this->manager->flush();
    }

    private function upsertOrder(array $importOrder)
    {
        $orderAmz = $this->manager->getRepository(AmazonOrder::class)->findOneBy([
            "amazonOrderId" => $importOrder['amazon-order-id'],
            'asin' => $importOrder['asin']
        ]);
        if (!$orderAmz) {
            $orderAmz = new AmazonOrder();
            $this->manager->persist($orderAmz);
            $new = true;
        } else {
            $new = false;
        }
        $orderAmz->importData($this->exchangeRate, $importOrder);
        if ($new) {
            $this->addProductAndBrand($orderAmz, $importOrder);
        }
        return $orderAmz;
    }


    private function addProductAndBrand(AmazonOrder $amz, $orderArray)
    {
        $sku = $this->getProductCorrelationSku($amz->getSku());
        $asin = $amz->getAsin();

        $product = $this->manager->getRepository(Product::class)->findOneBy([
            'asin' => $asin
        ]);
        if ($product) {
            if (!$product->getAsin()) {
                $product->setAsin();
            }
        } else {
            $product = $this->manager->getRepository(Product::class)->findOneBy([
                'sku' => $sku
            ]);
            if ($product) {
                if (!$product->getAsin()) {
                    $product->setAsin($asin);
                }
            } else {
                $product = new Product();
                $product->setAsin($amz->getAsin());
                $product->setDescription($orderArray['product-name']);
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
    private function getProductCorrelationSku(string $sku): string
    {
        $skuSanitized = strtoupper($sku);
        $productCorrelation = $this->manager->getRepository(ProductCorrelation::class)->findOneBy(['skuUsed' => $skuSanitized]);
        return $productCorrelation ? $productCorrelation->getSkuErp() : $skuSanitized;
    }
}
