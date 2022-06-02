<?php

namespace App\Service\Amazon\Report;

use App\Entity\AmazonOrder;
use App\Service\Amazon\AmzApi;
use App\Service\Amazon\Report\AmzApiImport;
use DateInterval;
use DateTime;

class AmzApiImportOrder extends AmzApiImport
{
    protected function createReport(?DateTime $dateTimeStart = null)
    {
        if (!$dateTimeStart) {
            $dateTimeStart = new DateTime('now');
            $dateTimeStart->sub(new DateInterval('P3D'));
        }
        return $this->amzApi->createReport($dateTimeStart, AmzApi::TYPE_REPORT_LAST_UPDATE_ORDERS);
    }

    protected function getLastReportContent()
    {
        return $this->amzApi->getContentLastReport(AmzApi::TYPE_REPORT_LAST_UPDATE_ORDERS);
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
        }
        $orderAmz->importData($this->exchangeRate, $importOrder);
        $this->addProductByAsin($orderAmz);
        return $orderAmz;
    }
}
