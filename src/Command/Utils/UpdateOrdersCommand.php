<?php

namespace App\Command\Utils;

use App\Entity\IntegrationChannel;
use App\Entity\Product;
use App\Entity\ProductCorrelation;
use App\Entity\WebOrder;
use App\Helper\Utils\CsvExtracter;
use App\Service\Aggregator\ApiAggregator;
use App\Service\Carriers\AriseTracking;
use DateInterval;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class UpdateOrdersCommand extends Command
{
    protected static $defaultName = 'app:update-orders';
    protected static $defaultDescription = 'Update orders';

    public function __construct(ManagerRegistry $managerRegistry, ApiAggregator $apiApggr)
    {
        /** @var \Doctrine\ORM\EntityManagerInterface */
        $this->manager = $managerRegistry->getManager();
        $this->apiApggr = $apiApggr;
        parent::__construct();
    }

    private $manager;

    private $apiApggr;


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $output->writeln("START CHANNEL ADVISOR");
        $this->updateOrderChannelAdvisor($output);
        $output->writeln("END CHANNEL ADVISOR");


        $channels = [
            IntegrationChannel::CHANNEL_ALIEXPRESS,
            IntegrationChannel::CHANNEL_FITBITEXPRESS,
            IntegrationChannel::CHANNEL_FLASHLED,
            IntegrationChannel::CHANNEL_OWLETCARE,
            IntegrationChannel::CHANNEL_MINIBATT,
        ];


        foreach($channels as $channel){
            $output->writeln("START ".$channel);
            $this->updateOrderChannel($output, $channel);
            $output->writeln("END ".$channel);
        }
        

        $channels = [
           // IntegrationChannel::CHANNEL_ARISE,
            IntegrationChannel::CHANNEL_AMAZFIT_ARISE,
            IntegrationChannel::CHANNEL_SONOS_ARISE,
        ];


        foreach($channels as $channel){
            $output->writeln("START ".$channel);
            $this->updateOrderMiraviaChannel($output, $channel);
            $output->writeln("END ".$channel);
        }



        return Command::SUCCESS;
    }



    protected function updateOrderChannelAdvisor($output){
        $batchSize = 1000;
        $i = 1;
        $q = $this->manager->createQuery('select a from App\Entity\WebOrder a where a.channel =:channel ')
            ->setParameter('channel', IntegrationChannel::CHANNEL_CHANNELADVISOR);
        foreach ($q->toIterable() as $webOrder) {
            if($webOrder->getStatus() == WebOrder::STATE_INVOICED){
                $webOrder->setStatus(WebOrder::STATE_COMPLETE);
            }
            $webOrder->setCarrierService(WebOrder::CARRIER_FBA);
            
            ++$i;
            if (($i % $batchSize) === 0) {
                $output->writeln("Saved  $i orders ");
                $this->manager->flush();
                $this->manager->clear();
            }
        }
        $output->writeln("Channels total  $i orders ");
        $this->manager->flush();
    }


    protected function updateOrderChannel($output, $channel){
        $batchSize = 1000;
        $i = 1;
        $dateCompar = new DateTime();
        $dateCompar->sub(new DateInterval('P45D'));

        $q = $this->manager->createQuery('select a from App\Entity\WebOrder a where a.channel =:channel')
            ->setParameter('channel',$channel);
        foreach ($q->toIterable() as $webOrder) {
            if($webOrder->getPurchaseDate() < $dateCompar && $webOrder->getStatus() == WebOrder::STATE_INVOICED){
                $webOrder->setStatus(WebOrder::STATE_COMPLETE);
            }
            
            $webOrder->setCarrierService(WebOrder::CARRIER_DHL);


        if ($webOrder->getTrackingUrl()) {
            $codeTracking =str_replace('https://clientesparcel.dhl.es/LiveTracking/ModificarEnvio/', '', $webOrder->getTrackingUrl());
            $webOrder->setTrackingCode($codeTracking);
        }

            
            ++$i;
            if (($i % $batchSize) === 0) {
                $output->writeln("Saved  $i orders ");
                $this->manager->flush();
                $this->manager->clear();
            }
        }
        $output->writeln("Channel : $channel total  $i orders ");
        $this->manager->flush();
    }






    protected function updateOrderMiraviaChannel($output, $channel){
        $batchSize = 10;
        $i = 1;

        $q = $this->manager->createQuery('select a from App\Entity\WebOrder a where a.channel =:channel')
            ->setParameter('channel',$channel);
        foreach ($q->toIterable() as $webOrder) {

            $webOrder->setSubchannel('Miravia.es');

            if($webOrder->getFulfilledBy()==WebOrder::FULFILLED_BY_SELLER){
                $webOrder->setCarrierService(WebOrder::CARRIER_DHL);
                if ($webOrder->getTrackingUrl()) {
                    $codeTracking =str_replace('https://clientesparcel.dhl.es/LiveTracking/ModificarEnvio/', '', $webOrder->getTrackingUrl());
                    $webOrder->setTrackingCode($codeTracking);
                }

                $text = "Mark as delivered on";
                $strlen= strlen($text);
                $lastLog=$webOrder->getLastLog();
                if($webOrder->getStatus()==WebOrder::STATE_INVOICED){
                    if (substr($lastLog, 0, $strlen) == $text) {
                        $webOrder->setStatus(WebOrder::STATE_COMPLETE);
                    } else {
                        $dateDeliveryExpedition = $webOrder->checkIfDelivered();
                        if ($dateDeliveryExpedition) {
                            $messageDelivery = $text .' '.$dateDeliveryExpedition->format("d-m-Y");
                            $webOrder->addLog($messageDelivery);
                            $webOrder->setStatus(WebOrder::STATE_COMPLETE);
                        }
                    }
                } 
            }

            if ($webOrder->getFulfilledBy() == WebOrder::FULFILLED_BY_EXTERNAL) {
                $webOrder->setCarrierService(WebOrder::CARRIER_ARISE);
                $webOrder->setFulfilledBy(WebOrder::FULFILLED_BY_SELLER);

                if($webOrder->getStatus()==WebOrder::STATE_INVOICED){
                    $orderArise = $this->apiApggr->getApi($channel)->getOrder($webOrder->getExternalNumber());
                    $trackingCode= null;
                    foreach ($orderArise->lines as $line) {
                        $trackingCode = $line->tracking_code;
                    }
        
                    if($trackingCode){
                        $webOrder->setTrackingCode($trackingCode);
                        $postCode =$orderArise->address_shipping->post_code;
                        $webOrder->setTrackingUrl(AriseTracking::getTrackingUrlBase($trackingCode, $postCode));

                        $dateDeliveryExpedition = $webOrder->checkIfDelivered();
                        if ($dateDeliveryExpedition) {
                            $messageDelivery = $text .' '.$dateDeliveryExpedition->format("d/m/Y");
                            $webOrder->addLog($messageDelivery);
                            $webOrder->setStatus(WebOrder::STATE_COMPLETE);
                        }
                    }

                } 

            }
            
           
            ++$i;
            if (($i % $batchSize) === 0) {
                $output->writeln("Saved  $i orders ");
                $this->manager->flush();
                $this->manager->clear();
            }
        }
        $output->writeln("Channel : $channel total  $i orders ");
        $this->manager->flush();
    }







}
