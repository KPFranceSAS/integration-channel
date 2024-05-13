<?php

namespace App\Service\Amazon\Report;

use App\Entity\AmazonOrder;
use App\Service\Amazon\AmzApi;
use App\Service\Amazon\Report\AmzApiImport;
use DateInterval;
use DateTime;
use Exception;

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



    public function createReportAndImportStartEnd(DateTime $dateTimeStart,DateTime $dateTimeEnd )
    {
        try {
            $badStatus = [AmzApi::STATUS_REPORT_CANCELLED, AmzApi::STATUS_REPORT_FATAL];
            $this->logger->info('Report creation ' . $this->getName());
            $report = $this->amzApi->createReport($dateTimeStart, $dateTimeEnd, AmzApi::TYPE_REPORT_LAST_UPDATE_ORDERS);
            $this->logger->info('Report processing ReportId = ' . $report->getReportId());
            for ($i = 0; $i < 30; $i++) {
                $j = ($i + 1) * self::WAITING_TIME;
                $this->logger->info("Wait  since $j seconds  reporting is done");
                sleep(self::WAITING_TIME);
                $reportState = $this->amzApi->getReport($report->getReportId());
                if ($reportState->getProcessingStatus() == AmzApi::STATUS_REPORT_DONE) {
                    $this->logger->info('Report processing done');
                    $datasReport = $this->amzApi->getContentReport($reportState->getReportDocumentId());
                    $this->importDatas($datasReport);
                    return;
                } elseif (in_array($reportState->getProcessingStatus(), $badStatus)) {
                    if ($reportState->getProcessingStatus() == AmzApi::STATUS_REPORT_CANCELLED) {
                        $this->logger->error('Report cancelled');
                        return;
                    } else {
                        throw new Exception('Fatal error to get report ' . $this->getName());
                    }
                } else {
                    $this->logger->info('Report processing not yet');
                }
            }
            throw new Exception('Report takes too long to be processed');
        } catch (Exception $e) {
            $this->mailer->sendEmail("[REPORT AMAZON " . $this->getName() . "]", $e->getMessage());
        }
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
