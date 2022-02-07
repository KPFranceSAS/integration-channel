<?php

namespace App\Service\Integrator;

use App\Entity\ProductCorrelation;
use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Helper\BusinessCentral\Model\SaleOrder;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\Integrator\IntegratorInterface;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use stdClass;


abstract class IntegratorParent implements IntegratorInterface
{


    protected $logger;

    protected $manager;

    protected $mailer;

    protected $businessCentralAggregator;


    public function __construct(ManagerRegistry $manager, LoggerInterface $logger, MailService $mailer, BusinessCentralAggregator $businessCentralAggregator)
    {
        $this->logger = $logger;
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->businessCentralAggregator = $businessCentralAggregator;
    }



    public function getBusinessCentralConnector($companyName)
    {
        return $this->businessCentralAggregator->getBusinessCentralConnector($companyName);
    }


    abstract public  function transformToAnBcOrder(stdClass $orderApi): SaleOrder;

    abstract public function integrateAllOrders();

    abstract public function getChannel();

    abstract public function getCompanyIntegration(stdClass $orderApi);

    abstract protected function getOrderId(stdClass $orderApi);

    public function processOrders($reIntegrate = false)
    {
        try {
            $this->errors = [];

            if ($reIntegrate) {
                $this->logger->info('Start reintegrations ' . $this->getChannel());
                $this->reIntegrateAllOrders();
                $this->logger->info('Ended reintegrations ' . $this->getChannel());
            } else {
                $this->logger->info('Start integrations ' . $this->getChannel());
                $this->integrateAllOrders();
                $this->logger->info('Ended integration ' . $this->getChannel());
            }

            if (count($this->errors) > 0) {
                $messageError = implode('<br/><br/>', array_unique($this->errors));
                throw new \Exception($messageError);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->mailer->sendEmail('[Order Integration ' . $this->getChannel() . '] Error', $e->getMessage());
        }
    }


    public function reIntegrateAllOrders()
    {
        $ordersToReintegrate = $this->manager->getRepository(WebOrder::class)->findBy(
            [
                'status' => WebOrder::STATE_ERROR,
                "channel" => $this->getChannel()
            ]
        );
        $this->logger->info(count($ordersToReintegrate) . ' orders to re-integrate ' . $this->getChannel());
        foreach ($ordersToReintegrate as $orderToReintegrate) {
            $this->logLine('>>> Reintegration of order ' . $orderToReintegrate->getExternalNumber());
            $this->reIntegrateOrder($orderToReintegrate);
        }
    }





    public function integrateOrder(stdClass $order)
    {
        $idOrder = $this->getOrderId($order);
        $company =  $this->getCompanyIntegration($order);
        $this->logLine(">>> Integration order marketplace " . $this->getChannel() . " $idOrder  in $company");
        if ($this->checkToIntegrateToInvoice($order)) {
            $this->logger->info('To integrate ');

            try {
                $webOrder = WebOrder::createOneFrom($order, $this->getChannel());
                $webOrder->setCompany($company);
                $this->manager->persist($webOrder);
                $this->checkAfterPersist($webOrder, $order);
                $this->addLogToOrder($webOrder, 'Order transformation to fit to ERP model');

                $orderBC = $this->transformToAnBcOrder($order);

                $businessCentralConnector = $this->businessCentralAggregator->getBusinessCentralConnector($webOrder->getCompany());
                $this->addLogToOrder($webOrder, 'Order creation in the ERP ' . $businessCentralConnector->getCompanyName());

                $erpOrder = $businessCentralConnector->createSaleOrder($orderBC->transformToArray());

                $this->addLogToOrder($webOrder, 'Order created in the ERP ' . $businessCentralConnector->getCompanyName() . ' with number ' . $erpOrder['number']);
                $webOrder->setStatus(WebOrder::STATE_SYNC_TO_ERP);
                $webOrder->setOrderErp($erpOrder['number']);
                $this->addLogToOrder($webOrder, 'Integration done ' . $erpOrder['number']);
            } catch (Exception $e) {
                $message = mb_convert_encoding($e->getMessage(), "UTF-8", "UTF-8");
                $webOrder->addError($message);
                $webOrder->setStatus(WebOrder::STATE_ERROR);
                $this->addError('Integration Problem ' . $idOrder . ' > ' . $message);
            }
            $this->manager->flush();
            $this->logger->info('Integration finished');
            return true;
        } else {
            $this->logger->info('No Integration');
            return false;
        }
    }


    protected function checkAfterPersist(WebOrder $order, stdClass $orderApi)
    {
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
            $orderBC = $this->transformToAnBcOrder($orderApi, $order->getCompany());

            $this->addLogToOrder($order, 'Order creation in the ERP');
            $erpOrder = $this->getBusinessCentralConnector($order->getCompany())->createSaleOrder($orderBC->transformToArray());
            $this->addLogToOrder($order, 'Order created in the ERP ' . $order->getCompany() . ' with number ' . $erpOrder['number']);

            $order->setStatus(WebOrder::STATE_SYNC_TO_ERP);
            $order->setOrderErp($erpOrder['number']);
            $this->addLogToOrder($order, 'Integration done ' . $erpOrder['number']);
        } catch (Exception $e) {
            $message =  mb_convert_encoding($e->getMessage(), "UTF-8", "UTF-8");
            $order->addError($message);
            $order->setStatus(WebOrder::STATE_ERROR);
            $this->addError('Reintegration Problem ' . $order->getExternalNumber() . ' > ' . $message);
            $this->addError($message);
        }
        $this->manager->flush();
        $this->logger->info('Reintegration finished');
        return true;
    }




    protected function checkToIntegrateToInvoice($order): bool
    {
        $idOrder = $this->getOrderId($order);
        $company = $this->getCompanyIntegration($order);
        if ($this->isAlreadyRecordedDatabase($idOrder)) {
            $this->logger->info('Is Already Recorded Database');
            return false;
        }
        if ($this->alreadyIntegratedErp($idOrder, $company)) {
            $this->logger->info('Is Already Recorded on ERP');
            return false;
        }
        return true;
    }


    protected function isAlreadyRecordedDatabase(string $idOrderApi): bool
    {
        $orderRecorded = $this->manager->getRepository(WebOrder::class)->findBy(
            [
                'externalNumber' => $idOrderApi,
                'channel' => $this->getChannel()
            ]
        );
        return count($orderRecorded) > 0;
    }


    protected function alreadyIntegratedErp(string $idOrderApi, string $company): bool
    {
        return $this->checkIfInvoice($idOrderApi, $company) || $this->checkIfOrder($idOrderApi, $company);
    }



    protected function checkIfOrder(string $idOrderApi, string $company): bool
    {
        $this->logger->info('Check order in BC ' . $idOrderApi);
        $saleOrder = $this->getBusinessCentralConnector($company)->getSaleOrderByExternalNumber($idOrderApi);
        return $saleOrder != null;
    }



    protected function checkIfInvoice(string $idOrderApi, string $company): bool
    {
        $this->logger->info('Check invoice in BC ' . $idOrderApi);
        $saleOrder = $this->getBusinessCentralConnector($company)->getSaleInvoiceByExternalNumber($idOrderApi);
        return $saleOrder != null;
    }




    protected function addLogToOrder(WebOrder $webOrder, string  $message)
    {
        $webOrder->addLog($message);
        $this->logger->info($message);
    }



    protected function addError($errorMessage)
    {
        $this->logger->error($errorMessage);
        $this->errors[] = $errorMessage;
    }



    protected function logLine($message)
    {
        $separator = str_repeat("-", strlen($message));
        $this->logger->info('');
        $this->logger->info($separator);
        $this->logger->info($message);
        $this->logger->info($separator);
    }


    protected function getProductCorrelationSku(string $sku, string $company): string
    {
        $skuSanitized = strtoupper($sku);
        $productCorrelation = $this->manager->getRepository(ProductCorrelation::class)->findOneBy(['skuUsed' => $skuSanitized]);
        $skuFinal = $productCorrelation ? $productCorrelation->getSkuErp() : $skuSanitized;

        $product = $this->getBusinessCentralConnector($company)->getItemByNumber($skuFinal);
        if (!$product) {
            throw new Exception("Product with Sku $skuFinal cannot be found in business central $company. Check Product correlation ");
        } else {
            return  $product['id'];
        }
    }
}
