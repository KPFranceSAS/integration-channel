<?php

namespace App\Service\Amazon;

use App\Entity\Product;
use App\Service\Amazon\AmzApi;
use App\Service\Amazon\AmzApiImport;
use DateTime;


class AmzApiImportProduct extends AmzApiImport
{
    protected function createReport(?DateTime $dateTimeStart = null)
    {
        $dateTimeStart = new DateTime('now');
        return $this->amzApi->createReport($dateTimeStart, AmzApi::TYPE_REPORT_MANAGE_INVENTORY_ARCHIVED);
    }

    protected function getLastReportContent()
    {
        return $this->amzApi->getContentLastReport(AmzApi::TYPE_REPORT_MANAGE_INVENTORY_ARCHIVED);
    }

    protected function upsertData(array $importOrder)
    {
        $product = $this->manager->getRepository(Product::class)->findOneBy([
            'asin' => $importOrder['asin']
        ]);

        if ($product) {
            if (!$product->getFnsku()) {
                $product->setFnsku($importOrder['fnsku']);
            }
            return $product;
        }

        $sku = $this->getProductCorrelationSku($importOrder['sku']);
        $product = $this->manager->getRepository(Product::class)->findOneBy([
            'sku' => $sku
        ]);
        if ($product) {
            if (!$product->getAsin()) {
                $product->setAsin($importOrder['asin']);
            }
            if (!$product->getFnsku()) {
                $product->setFnsku($importOrder['fnsku']);
            }
            return;
        } else {
            $this->logger->info('New product ' . $sku);
            $product = new Product();
            $product->setAsin($importOrder['asin']);
            $product->setDescription($importOrder['product-name']);
            $product->setFnsku($importOrder['fnsku']);
            $product->setSku($sku);
            $this->manager->persist($product);
        }
    }
}
