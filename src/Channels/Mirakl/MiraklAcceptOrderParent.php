<?php

namespace App\Channels\Mirakl;

use App\BusinessCentral\ProductStockFinder;
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

    protected $productStockFinder;

    /** @var App\Channels\Mirakl\MiraklApiParent */
    protected $apiClient;

    public function __construct(
        LoggerInterface $logger,
        MailService $mailer,
        ApiAggregator $apiAggregator,
        ProductStockFinder $productStockFinder
    ) {
        $this->logger= $logger;
        $this->mailer = $mailer;
        $this->productStockFinder = $productStockFinder;
        $this->apiClient = $apiAggregator->getService($this->getChannel());
    }



    abstract public function getChannel() : string;


    
    /**
     * process all invocies directory
     *
     * @return void
     */
    public function acceptAllOrders(): void
    {
        $ordersApi = $this->apiClient->getAllOrdersToAccept();
        if(count($ordersApi)>0) {
            $this->logger->info(count($ordersApi).' orders to accept');
            foreach ($ordersApi as $orderApi) {
                try {
                    if($this->checkStock($orderApi)) {
                        $accepted = $this->apiClient->markOrderAsAccepted($orderApi);
                        $this->logger->info('Marked as accepted on '.$this->getChannel());
                    } else {
                        $refused = $this->apiClient->markOrderAsRefused($orderApi);
                        $this->logger->info('Marked as refused on '.$this->getChannel());
                        $this->mailer->sendEmail("[".$this->getChannel()."] Refused ", "Refused >>> ".$this->getChannel()." ".$orderApi['id']);

                    }
                    
                    
                } catch (Exception $exception) {
                    $this->mailer->sendEmail("[".$this->getChannel()."] Acceptation problem ", 'Problem acceptation '.$this->getChannel().' #' . $orderApi['id'] . ' > ' . $exception->getMessage());
                }
            }
        } else {
            $this->logger->info('No orders to accept');
        }
        
    }





    public function checkStock($orderApi):bool
    {
        $stockGood = true;
        foreach($orderApi['order_lines'] as $orderLine) {
            $stockBC = $this->productStockFinder->getFinalStockProductWarehouse($orderLine['offer']['sku']);
            if($stockBC < $orderLine['quantity']) {
                $this->logger->info('Miss stock for '.$orderLine['offer']['sku']);
                $stockGood = false;
            }
        }
        return $stockGood;
    }





}
