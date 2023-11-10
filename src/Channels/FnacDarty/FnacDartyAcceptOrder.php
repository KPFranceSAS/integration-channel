<?php

namespace App\Channels\FnacDarty;

use App\BusinessCentral\ProductStockFinder;
use App\Helper\MailService;
use App\Service\Aggregator\ApiAggregator;
use Exception;
use Psr\Log\LoggerInterface;

abstract class FnacDartyAcceptOrder
{

    protected $logger;

    protected $mailer;

    protected $apiClient;

    protected $productStockFinder;

    public function __construct(
        LoggerInterface $logger,
        MailService $mailer,
        ProductStockFinder $productStockFinder,
        ApiAggregator $apiAggregator
    ) {
        $this->logger= $logger;
        $this->mailer = $mailer;
        $this->apiClient = $apiAggregator->getService($this->getChannel());
        $this->productStockFinder  = $productStockFinder;
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
                    $this->logger->info('Checking order '.json_encode($orderApi));
                    if($this->checkStock($orderApi)) {
                        $accepted = $this->apiClient->markOrderAsAccepted($orderApi['order_id']);
                        $this->logger->info('Marked as accepted on '.$this->getChannel());
                    
                    } else {
                        $accepted = $this->apiClient->markOrderAsRefused($orderApi['order_id']);
                        $this->logger->info('Marked as refused on '.$this->getChannel());
                    }


                    
                } catch (Exception $exception) {
                    $this->mailer->sendEmail("[".$this->getChannel()."] Acceptation problem ", 'Problem acceptation '.$this->getChannel().' #' . $orderApi['id'] . ' > ' . $exception->getMessage());
                }
            }
        } else {
            $this->logger->info('No orders to accept');
        }
        
    }




    public function checkStock(array $orderApi):bool
    {
        $stockGood = true;
        foreach($orderApi['order_detail'] as $orderLine) {
            $stockBC = $this->productStockFinder->getFinalStockProductWarehouse($orderLine['offer_seller_id']);
            if($stockBC < $orderLine['quantity']) {
                $this->logger->info('Miss stock for '.$orderLine['offer_seller_id']);
                $stockGood = false;
            }
        }
        return $stockGood;
    }


}
