<?php

namespace App\Service\Aggregator;

use App\Entity\WebOrder;
use App\Helper\MailService;
use App\Helper\Traits\TraitServiceLog;
use App\Service\Aggregator\ApiAggregator;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;

abstract class UpdateDeliveryParent
{
    use TraitServiceLog;
    protected $logger;

    protected $manager;

    protected $mailer;

    protected $businessCentralAggregator;

    protected $tracker;

    protected $apiAggregator;

    protected $errors;


    public function __construct(ManagerRegistry $manager, LoggerInterface $logger, MailService $mailer, ApiAggregator $apiAggregator)
    {
        $this->logger = $logger;
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->apiAggregator = $apiAggregator;
    }


    abstract public function getChannel();


    public function getApi()
    {
        return $this->apiAggregator->getApi($this->getChannel());
    }
   



    /**
     *
     *
     * @return void
     */
    public function updateStatusDeliveries()
    {
        try {
            $this->errors = [];

            $this->logger->info('Start updating sale orders ' . $this->getChannel());
            /** @var array[\App\Entity\WebOrder] */
            $ordersToSend = $this->manager->getRepository(WebOrder::class)->findBy(
                [
                    "status" => WebOrder::STATE_INVOICED,
                    "channel" => $this->getChannel(),
                    "fulfilledBy" => WebOrder::FULFILLED_BY_SELLER,
                ]
            );
            $this->logger->info(count($ordersToSend) . ' sale orders to update delivery');
            foreach ($ordersToSend as $orderToSend) {
                $this->logLine('>>> Update sale Order '.$orderToSend->getChannel().' '. $orderToSend->getExternalNumber());
                $this->updateDeliverySaleOrder($orderToSend);
            }
            $this->logger->info('Ended updating delivery sale orders ' . $this->getChannel());
            if (count($this->errors) > 0) {
                throw new Exception(implode('<br/><br/>', $this->errors));
            }
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->mailer->sendEmailChannel($this->getChannel(), 'Delivery updates', $e->getMessage());
        }
    }



    protected function postUpdateDelivery(WebOrder $order)
    {
        return true;
    }



    protected function updateDeliverySaleOrder(WebOrder $webOrder)
    {
        try {
            $dateDelivery = $webOrder->checkifDelivered();
            if ($dateDelivery) {
                    $this->logger->info('Is delivered '.$dateDelivery->format('d/m/Y'));
                    $messageDelivery = 'Mark as delivered on '.$dateDelivery->format('d/m/Y');
                    if ($webOrder->haveNoLogWithMessage($messageDelivery)) {
                        $markOk =  $this->postUpdateDelivery($webOrder);
                        if ($markOk) {
                            $this->logger->info($messageDelivery);
                            $webOrder->addLog($messageDelivery);
                            $webOrder->setStatus(WebOrder::STATE_COMPLETE);
                        }
                    } else {
                        $this->logger->info('Already marked as delivered');
                        $webOrder->setStatus(WebOrder::STATE_COMPLETE);
                    }
                } else {
                    $this->logger->info('Not yet delivered ');
                }
        } catch (Exception $e) {
            $message =  mb_convert_encoding($e->getMessage(), "UTF-8", "UTF-8");
            $this->addErrorToOrder($webOrder, $webOrder->getExternalNumber() . ' >> ' . $message);
        }
        $this->manager->flush();
    }
}
