<?php

namespace App\Service\Amazon;

use App\Entity\AmazonRemovalOrder;
use App\Entity\Product;
use App\Service\Amazon\AmzApi;
use App\Service\Amazon\AmzApiImport;
use DateInterval;
use DateTime;


class AmzApiImportRemovalOrder extends AmzApiImport
{

    protected function createReport(?DateTime $dateTimeStart = null)
    {
        if (!$dateTimeStart) {
            $dateTimeStart = new DateTime('now');
            $dateTimeStart->sub(new DateInterval('P3D'));
        }
        return $this->amzApi->createReport($dateTimeStart, AmzApi::TYPE_REPORT_REMOVAL_ORDER_DETAIL);
    }

    protected function getLastReportContent()
    {
        return $this->amzApi->getContentLastReport(AmzApi::TYPE_REPORT_REMOVAL_ORDER_DETAIL);
    }

    protected function upsertData(array $importOrder)
    {
        $orderAmz = $this->manager->getRepository(AmazonRemovalOrder::class)->findOneBy([
            "orderId" => $importOrder['order-id'],
            'fnsku' => $importOrder['fnsku']
        ]);
        if (!$orderAmz) {
            $orderAmz = new AmazonRemovalOrder();
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


    protected function addProductAndBrand($amz, $orderArray)
    {
        $sku = $this->getProductCorrelationSku($amz->getSku());
        $fnsku = $amz->getFnsku();

        $product = $this->manager->getRepository(Product::class)->findOneBy([
            'fnsku' => $fnsku
        ]);
        if (!$product) {
            $product = $this->manager->getRepository(Product::class)->findOneBy([
                'sku' => $sku
            ]);
            if ($product) {
                if (!$product->getFnsku()) {
                    $product->setFnsku($fnsku);
                }
            } else {
                $this->logger->info('New product ' . $sku);
                $product = new Product();
                $product->setDescription('Unknown');
                $product->setFnsku($orderArray['fnsku']);
                $product->setSku($sku);
                $this->manager->persist($product);
                $this->manager->flush();
            }
        }
        $amz->setProduct($product);
    }
}
