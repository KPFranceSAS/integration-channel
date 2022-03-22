<?php

namespace App\Service\Amazon;

use App\Entity\AmazonOrder;
use App\Service\Amazon\AmzApi;
use App\Service\Amazon\AmzApiImport;
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
