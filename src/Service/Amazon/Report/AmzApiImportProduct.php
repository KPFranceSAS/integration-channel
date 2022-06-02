<?php

namespace App\Service\Amazon\Report;

use App\Entity\Product;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Service\Amazon\AmzApi;
use App\Service\Amazon\Report\AmzApiImport;
use DateInterval;
use DateTime;
use Exception;

class AmzApiImportProduct extends AmzApiImport
{
    public function createReportAndImport(?DateTime $dateTimeStart = null)
    {
        $this->errorProducts = [];

        try {
            $createdSince = new DateTime('now');
            $createdSince->sub(new DateInterval('PT2H'));
            $marketplaces = $this->amzApi->getAllMarketplaces();
            $reports = $this->amzApi->getAllReports(
                [AmzApi::TYPE_REPORT_MANAGE_INVENTORY_ARCHIVED],
                [AmzApi::STATUS_REPORT_DONE],
                $createdSince
            );

            foreach ($marketplaces as $marketplace) {
                foreach ($reports as $report) {
                    if (in_array($marketplace, $report->getMarketplaceIds())) {
                        $datasReport = $this->amzApi->getContentReport($report->getReportDocumentId());
                        $this->importDatas($datasReport);
                        break;
                    }
                }
            }

            if (count($this->errorProducts) > 0) {
                $message =  implode('<br/>', $this->errorProducts);
                $this->mailer->sendEmail("[REPORT AMAZON " . $this->getName() . "]", $message);
            }
        } catch (Exception $e) {
            $this->mailer->sendEmail("[REPORT AMAZON " . $this->getName() . "]", $e->getMessage());
        }
    }



    protected function createReport(?DateTime $dateTimeStart = null)
    {
    }

    protected function getLastReportContent()
    {
        return $this->amzApi->getContentLastReport(AmzApi::TYPE_REPORT_MANAGE_INVENTORY_ARCHIVED);
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
            } else {
                $this->errorProducts[] = 'Product ' . $sku . ' not found in Business central';
            }
        }
    }
}
