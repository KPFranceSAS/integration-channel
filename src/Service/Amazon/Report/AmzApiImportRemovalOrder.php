<?php

namespace App\Service\Amazon\Report;

use App\Entity\AmazonRemovalOrder;
use App\Entity\Product;
use App\Service\Amazon\AmzApi;
use App\Service\Amazon\Report\AmzApiImport;
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
        }
        $orderAmz->importData($this->exchangeRate, $importOrder);
        $this->addProductByFnsku($orderAmz);

        return $orderAmz;
    }
}
