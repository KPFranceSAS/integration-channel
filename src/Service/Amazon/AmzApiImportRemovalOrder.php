<?php

namespace App\Service\Amazon;

use App\Entity\AmazonRemovalOrder;
use App\Entity\Product;
use App\Service\Amazon\AmzApi;
use App\Service\Amazon\AmzApiImport;
use DateTime;


class AmzApiImportRemovalOrder extends AmzApiImport
{

    protected function createReport(?DateTime $dateTimeStart = null)
    {
        return $this->amzApi->createReportRemovalOrderByLastUpdate($dateTimeStart);
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
            $importOrder["product-name"] = "Not found";
            $this->addProductAndBrand($orderAmz, $importOrder);
        }
        return $orderAmz;
    }
}
