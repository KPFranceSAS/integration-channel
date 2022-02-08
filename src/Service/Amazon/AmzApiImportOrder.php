<?php

namespace App\Service\Amazon;

use App\Entity\AmazonOrder;
use App\Service\Amazon\AmzApiImport;
use DateTime;


class AmzApiImportOrder extends AmzApiImport
{

    protected function createReport(?DateTime $dateTimeStart = null)
    {
        return $this->amzApi->createReportOrdersByLastUpdate($dateTimeStart);
    }

    protected function getLastReportContent()
    {
        return $this->amzApi->getContentLastReportOrdersByLastUpdate();
    }

    protected function upsertData(array $importOrder)
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
}
