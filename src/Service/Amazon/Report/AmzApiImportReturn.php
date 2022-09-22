<?php

namespace App\Service\Amazon\Report;

use App\Entity\AmazonOrder;
use App\Entity\AmazonReturn;
use App\Helper\Utils\DatetimeUtils;
use App\Service\Amazon\AmzApi;
use App\Service\Amazon\Report\AmzApiImport;
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
            $ordersAmz = $this->manager->getRepository(AmazonOrder::class)->findBy([
                "amazonOrderId" => $importOrder['order-id'],
            ]);

            if (count($ordersAmz)>0) {
                $returnAmz->setMarketplaceName($ordersAmz[0]->getSalesChannel());
            }
        }

        

        

        $this->addProductByFnsku($returnAmz);
        return $returnAmz;
    }
}
