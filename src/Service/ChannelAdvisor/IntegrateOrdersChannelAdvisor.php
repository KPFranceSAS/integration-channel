<?php

namespace App\Service\ChannelAdvisor;

use App\Entity\IntegrationFile;
use App\Entity\WebOrder;
use App\Service\BusinessCentral\BusinessCentralConnector;
use App\Service\ChannelAdvisor\ChannelWebservice;
use App\Service\ChannelAdvisor\TransformOrder;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Psr\Log\LoggerInterface;
use stdClass;


/**
 * Services that will get through the API the order from ChannelAdvisor
 * 
 */
class IntegrateOrdersChannelAdvisor
{

    /**
     *
     * @var  LoggerInterface
     */
    private $logger;

    /**
     *
     * @var ChannelWebservice
     */
    private $channel;

    /**
     *
     * @var  ObjectManager
     */
    private $manager;

    /**
     *
     * @var BusinessCentralConnector
     */
    private $businessCentralConnector;

    /**
     *
     * @var  TransformOrder 
     */
    private $transformOrder;



    public function __construct(
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        ChannelWebservice $channel,
        BusinessCentralConnector $businessCentralConnector,
        TransformOrder $transformOrder
    ) {
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->channel = $channel;
        $this->manager = $manager->getManager();
        $this->channel = $channel;
        $this->businessCentralConnector = $businessCentralConnector;
        $this->transformOrder = $transformOrder;
    }


    /**
     * 
     * 
     * @return void
     */
    public function processOrders($reIntegrate = false)
    {
        try {
            $this->errors = [];

            if ($reIntegrate) {
                $this->logger->info('Start reintegrations');
                $this->reIntegrateAllOrders();
                $this->logger->info('Ended reintegrations');
            } else {
                $this->logger->info('Start integrations');
                $this->integrateAllOrders();
                $this->logger->info('Ended integration');
            }

            if (count($this->errors) > 0) {
                $messageError = implode('<br/>', array_unique($this->errors));
                throw new \Exception($messageError);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->mailer->sendEmail('[Order Integration] Error', $e->getMessage(), 'stephane.lanjard@kpsport.com');
        }
    }



    /**
     * 
     * 
     * @return void
     */
    public function reIntegrateAllOrders()
    {
        $ordersToReintegrate = $this->manager->getRepository(WebOrder::class)->findBy(
            [
                'status' => WebOrder::STATE_ERROR,
                "channel" => WebOrder::CHANNEL_CHANNELADVISOR
            ]
        );
        $this->logger->info(count($ordersToReintegrate) . ' orders to re-integrate');
        foreach ($ordersToReintegrate as $orderToReintegrate) {
            $this->logLine('>>> Reintegration of order ' . $orderToReintegrate->getExternalNumber());
            $this->reIntegrateOrder($orderToReintegrate);
        }
    }





    protected function addError($errorMessage)
    {
        $this->logger->error($errorMessage);
        $this->errors[] = $errorMessage;
    }


    /**
     * process all invocies directory
     *
     * @return void
     */
    protected function integrateAllOrders()
    {
        $counter = 0;
        $ordersApi = $this->channel->getNewOrdersByBatch(true);
        $this->logLine('Integration first batch');
        foreach ($ordersApi->value as $orderApi) {
            if ($this->integrateOrder($orderApi)) {
                $counter++;
                $this->logger->info("Orders integrated : $counter ");
            }
        }

        while (true) {
            if (property_exists($ordersApi, '@odata.nextLink')) {

                $this->logLine('Integration next batch');
                $ordersApi = $this->channel->getNextResults($ordersApi->{'@odata.nextLink'});
                foreach ($ordersApi->value as $orderApi) {
                    if ($this->integrateOrder($orderApi)) {
                        $counter++;
                        $this->logger->info("Orders integrated : $counter ");
                    }
                }
            } else {
                return;
            }
        }
    }


    public function logLine($message)
    {
        $separator = str_repeat("-", strlen($message));
        $this->logger->info('');
        $this->logger->info($separator);
        $this->logger->info($message);
        $this->logger->info($separator);
    }


    /**
     * Integrates order 
     * Checks if already integrated in BusinessCentral (invoice or order)
     * 
     * @param stdClass $order
     * @return void
     */
    protected function integrateOrder(stdClass $order)
    {
        $this->logLine('>>> Integration order marketplace ' . $order->SiteName . " " . $order->SiteOrderID);
        if ($this->checkToIntegrateToInvoice($order)) {
            $this->logger->info('To integrate ');

            try {
                $webOrder = WebOrder::createOneFromChannelAdvisor($order);
                $this->manager->persist($webOrder);
                $this->addLogToOrder($webOrder, 'Marked on channel advisor as exported');
                $this->channel->markOrderAsExported($order->ID);
                $this->addLogToOrder($webOrder, 'Order transformation to fit to ERP model');
                $orderBC = $this->transformOrder->transformToAnFBAOrder($order);
                $this->addLogToOrder($webOrder, 'Order creation in the ERP');
                $erpOrder = $this->businessCentralConnector->createSaleOrder($orderBC->transformToArray());
                $this->addLogToOrder($webOrder, 'Order created in the ERP with number ' . $erpOrder['number']);
                $webOrder->setStatus(WebOrder::STATE_SYNC_TO_ERP);
                $webOrder->setOrderErp($erpOrder['number']);
                $this->addLogToOrder($webOrder, 'Integration done ' . $erpOrder['number']);
            } catch (Exception $e) {
                $message =  mb_convert_encoding($e->getMessage(), "UTF-8", "UTF-8");
                $webOrder->addError($message);
                $webOrder->setStatus(WebOrder::STATE_ERROR);
                $this->addError($message);
            }
            $this->manager->flush();
            $this->logger->info('Integration finished');
            return true;
        } else {
            $this->logger->info('No Integration');
            return false;
        }
    }



    /**
     * Integrates order 
     * Checks if already integrated in BusinessCentral (invoice or order)
     * 
     */
    public function reIntegrateOrder(WebOrder $order)
    {
        try {
            $order->cleanErrors();
            $this->addLogToOrder($order, 'Attempt to new integration');
            $this->addLogToOrder($order, 'Order transformation to fit to ERP model');

            $orderApi = $order->getOrderContent();

            $orderBC = $this->transformOrder->transformToAnFBAOrder($orderApi);
            $this->addLogToOrder($order, 'Order creation in the ERP');
            $erpOrder = $this->businessCentralConnector->createSaleOrder($orderBC->transformToArray());
            $this->addLogToOrder($order, 'Order created in the ERP with number ' . $erpOrder['number']);
            $order->setStatus(WebOrder::STATE_SYNC_TO_ERP);
            $order->setOrderErp($erpOrder['number']);
            $this->addLogToOrder($order, 'Integration done ' . $erpOrder['number']);
        } catch (Exception $e) {
            $message =  mb_convert_encoding($e->getMessage(), "UTF-8", "UTF-8");
            $order->addError($message);
            $order->setStatus(WebOrder::STATE_ERROR);
            $this->addError($message);
        }
        $this->manager->flush();
        $this->logger->info('Integration finished');
        return true;
    }






    protected function addLogToOrder(WebOrder $webOrder, $message)
    {
        $webOrder->addLog($message);
        $this->logger->info($message);
    }





    /**
     * INtegrates order 
     * Checks if already integrated in BusinessCentral (invoice or order) 
     * Check if status fits to this process
     * 
     * @param stdClass $order
     * @return void
     */
    protected function checkToIntegrateToInvoice($order): bool
    {
        if ($this->isAlreadyRecordedDatabase($order)) {
            $this->channel->markOrderAsExported($order->ID);
            $this->logger->info('Marked on channel advisor as exported');
            $this->logger->info('Is Already Recorded Database');
            return false;
        }
        if ($this->alreadyIntegratedErp($order)) {
            $this->channel->markOrderAsExported($order->ID);
            $this->logger->info('Marked on channel advisor as exported');
            $this->logger->info('Is Already Recorded on ERP');
            return false;
        }
        if (!$this->checkStatusToInvoice($order)) {

            $this->logger->info('Status is not good for integration');
            return false;
        }
        return true;
    }

    /**
     * Check is Already Recorded Database
     * 
     * @param stdClass $orderApi
     * @return boolean
     */
    protected function isAlreadyRecordedDatabase($orderApi): bool
    {
        $orderRecorded = $this->manager->getRepository(WebOrder::class)->findBy(
            ['externalNumber' => $orderApi->SiteOrderID]
        );
        return count($orderRecorded) > 0;
    }


    /**
     * Check status of order 
     * 
     * @param stdClass $orderApi
     * @return boolean
     */
    protected function alreadyIntegratedErp($orderApi): bool
    {
        return $this->checkIfInvoice($orderApi) || $this->checkIfOrder($orderApi)  || $this->checkIfPostedInvoice($orderApi);
    }


    /**
     * 
     * @param stdClass $orderApi
     * @return boolean
     */
    protected function checkIfOrder($orderApi): bool
    {
        $this->logger->info('Check order in BC ' . $orderApi->SiteOrderID);
        $saleOrder = $this->businessCentralConnector->getSaleOrderByExternalNumber($orderApi->SiteOrderID);
        return $saleOrder != null;
    }



    /**
     * 
     * @param stdClass $orderApi
     * @return boolean
     */
    protected function checkIfInvoice($orderApi): bool
    {
        $this->logger->info('Check invoice in BC ' . $orderApi->SiteOrderID);
        $saleOrder = $this->businessCentralConnector->getSaleInvoiceByExternalNumber($orderApi->SiteOrderID);
        return $saleOrder != null;
    }


    /**
     * 
     * 
     * @param stdClass $orderApi
     * @return boolean
     */
    protected function checkIfPostedInvoice($orderApi): bool
    {
        $this->logger->info('Check post invoice in export file ' . $orderApi->SiteOrderID);
        $files = $this->manager->getRepository(IntegrationFile::class)->findBy(
            [
                'externalOrderId' => $orderApi->SiteOrderID,
                'documentType' => IntegrationFile::TYPE_INVOICE
            ]
        );
        return count($files) > 0;
    }





    /**
     * Check status of order 
     * 
     * @param stdClass $order
     * @return boolean
     */
    protected function checkStatusToInvoice($orderApi): bool
    {
        if (($orderApi->DistributionCenterTypeRollup == 'ExternallyManaged' && $orderApi->ShippingStatus == 'Shipped')) {
            $this->logger->info('Status OK');
            return true;
        } else {
            $this->logger->info("X__Status Bad " . $orderApi->DistributionCenterTypeRollup . " " . $orderApi->ShippingStatus);
            return false;
        }
    }
}
