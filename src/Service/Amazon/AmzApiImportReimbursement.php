<?php

namespace App\Service\Amazon;

use App\Entity\AmazonReimbursement;
use App\Service\Amazon\AmzApiImport;
use DateTime;


class AmzApiImportReimbursement extends AmzApiImport
{

    protected function createReport(?DateTime $dateTimeStart = null)
    {
        return $this->amzApi->createReportReimbursementsByLastUpdate($dateTimeStart);
    }

    protected function getLastReportContent()
    {
        return $this->amzApi->getContentLastReportReimbursementByLastUpdate();
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
            $reimbursementAmz->importData($this->exchangeRate, $importOrder);
            $this->addProductAndBrand($reimbursementAmz, $importOrder);
        }

        if ($importOrder['original-reimbursement-id']) {
            $reimbursementOriginalAmz = $this->manager->getRepository(AmazonReimbursement::class)->findOneBy([
                "reimbursementId" => $importOrder['original-reimbursement-id'],
                'asin' => $importOrder['asin']
            ]);
            if ($reimbursementOriginalAmz) {
                $reimbursementAmz->setOriginalReimbursement($reimbursementOriginalAmz);
            }
        }

        return $reimbursementAmz;
    }
}
