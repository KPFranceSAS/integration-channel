<?php

namespace App\Channels\Mirakl;

use App\Channels\Mirakl\MiraklApiParent;
use App\Entity\WebOrder;
use App\Helper\MailService;
use App\Helper\Utils\DatetimeUtils;
use App\Service\Aggregator\ApiAggregator;
use App\Service\Aggregator\IntegratorParent;
use App\Service\Carriers\UpsGetTracking;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;

abstract class MiraklAcceptOrderParent
{

    protected $logger;

    protected $mailer;

    /** @var App\Channels\Mirakl\MiraklApiParent */
    protected $apiClient;

    public function __construct(
        LoggerInterface $logger,
        MailService $mailer,
        ApiAggregator $apiAggregator
    ) {
        $this->logger= $logger;
        $this->mailer = $mailer;
        $this->apiClient = $apiAggregator->getService($this->getChannel());
    }



    abstract public function getChannel() : string;


    
    /**
     * process all invocies directory
     *
     * @return void
     */
    public function acceptAllOrders()
    {
        $ordersApi = $this->apiClient->getAllOrdersToAccept();
        if(count($ordersApi)>0) {
            $this->logger->info(count($ordersApi).' orders to accept');
            foreach ($ordersApi as $orderApi) {
                try {
                    $accepted = $this->apiClient->markOrderAsAccepted($orderApi);
                    $this->logger->info('Marked as accepted on '.$this->getChannel());
                    
                } catch (Exception $exception) {
                    $this->mailer->sendEmail("[".$this->getChannel()."] Acceptation problem ", 'Problem acceptation '.$this->getChannel().' #' . $orderApi['id'] . ' > ' . $exception->getMessage());
                }
            }
        } else {
            $this->logger->info('No orders to accept');
        }
        
    }

}
