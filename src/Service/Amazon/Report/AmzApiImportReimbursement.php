<?php

namespace App\Service\Amazon\Report;

use App\Entity\AmazonOrder;
use App\Entity\AmazonReimbursement;
use App\Service\Amazon\AmzApi;
use App\Service\Amazon\Report\AmzApiImport;
use DateInterval;
use DateTime;

class AmzApiImportReimbursement extends AmzApiImport
{
    protected function createReport(?DateTime $dateTimeStart = null)
    {
        if (!$dateTimeStart) {
            $dateTimeStart = new DateTime('now');
            $dateTimeStart->sub(new DateInterval('P3D'));
        }
        return $this->amzApi->createReport($dateTimeStart, AmzApi::TYPE_REPORT_REIMBURSEMENT);
    }

    protected function getLastReportContent()
    {
        return $this->amzApi->getContentLastReport(AmzApi::TYPE_REPORT_REIMBURSEMENT);
    }

    protected function upsertData(array $importOrder)
    {
        $reimbursementAmz = $this->manager->getRepository(AmazonReimbursement::class)->findOneBy([
            "reimbursementId" => $importOrder['reimbursement-id'],
            'asin' => $importOrder['asin']
        ]);
        if (!$reimbursementAmz) {
            $reimbursementAmz = new AmazonReimbursement();
            $this->manager->persist($reimbursementAmz);
            

            $ordersAmz = $this->manager->getRepository(AmazonOrder::class)->findBy([
                "amazonOrderId" => $importOrder['amazon-order-id'],
            ]);

            if (count($ordersAmz)>0) {
                $reimbursementAmz->setMarketplaceName($ordersAmz[0]->getSalesChannel());
            }
        }
        $reimbursementAmz->importData($this->exchangeRate, $importOrder);
        $this->addProductByFnsku($reimbursementAmz);
        if ($importOrder['original-reimbursement-id']) {
            $reimbursementOriginalAmz = $this->manager->getRepository(AmazonReimbursement::class)->findOneBy([
                "reimbursementId" => $importOrder['original-reimbursement-id'],
                'asin' => $importOrder['asin']
            ]);
            if ($reimbursementOriginalAmz) {
                $reimbursementAmz->setOriginalReimbursement($reimbursementOriginalAmz);
                $reimbursementAmz->setAmazonOrderId($reimbursementOriginalAmz->getAmazonOrderId());
                $reimbursementAmz->setMarketplaceName($reimbursementOriginalAmz->getMarketplaceName());
            }
        }
        return $reimbursementAmz;
    }
}
