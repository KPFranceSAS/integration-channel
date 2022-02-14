<?php

namespace App\Service\Amazon;

use App\Entity\AmazonReturn;
use App\Helper\Utils\DatetimeUtils;
use App\Service\Amazon\AmzApiImport;
use DateTime;


class AmzApiImportReturn extends AmzApiImport
{

    protected function createReport(?DateTime $dateTimeStart = null)
    {
        return $this->amzApi->createReportReturnsByLastUpdate($dateTimeStart);
    }

    protected function getLastReportContent()
    {
        return $this->amzApi->getContentLastReportReturnByLastUpdate();
    }

    protected function upsertData(array $importOrder)
    {
        $returnAmz = $this->manager->getRepository(AmazonReturn::class)->findOneBy([
            "orderId" => $importOrder['order-id'],
            'asin' => $importOrder['asin'],
            'returnDate' => DatetimeUtils::transformFromIso8601($importOrder['return-date'])
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
