<?php

namespace App\Service\Amazon;

use App\Entity\AmazonReturn;
use App\Helper\Utils\DatetimeUtils;
use App\Service\Amazon\AmzApi;
use App\Service\Amazon\AmzApiImport;
use DateInterval;
use DateTime;


class AmzApiImportReturn extends AmzApiImport
{

    protected function createReport(?DateTime $dateTimeStart = null)
    {
        if (!$dateTimeStart) {
            $dateTimeStart = new DateTime('now');
            $dateTimeStart->sub(new DateInterval('P3D'));
        }
        return $this->amzApi->createReport($dateTimeStart, AmzApi::TYPE_REPORT_RETURNS_DATA);
    }

    protected function getLastReportContent()
    {
        return $this->amzApi->getContentLastReport(AmzApi::TYPE_REPORT_RETURNS_DATA);
    }

    protected function upsertData(array $importOrder)
    {
        $returnAmz = $this->manager->getRepository(AmazonReturn::class)->findOneBy([
            "orderId" => $importOrder['order-id'],
            'licensePlateNumber' => $importOrder['license-plate-number'],
        ]);
        if (!$returnAmz) {
            $returnAmz = new AmazonReturn();
            $this->manager->persist($returnAmz);
            $returnAmz->importData($importOrder);
            $this->addProductAndBrand($returnAmz, $importOrder);
        }



        return $returnAmz;
    }
}
