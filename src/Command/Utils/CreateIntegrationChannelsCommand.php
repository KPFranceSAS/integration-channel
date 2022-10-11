<?php

namespace App\Command\Utils;

use App\Entity\IntegrationChannel;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateIntegrationChannelsCommand extends Command
{
    protected static $defaultName = 'app:create-integration-channels';
    protected static $defaultDescription = 'Import integration channels';

    public function __construct(ManagerRegistry $manager)
    {
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    private $manager;


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $integrationChannels=[
            [
                'code'=>IntegrationChannel::CHANNEL_CHANNELADVISOR, 
                'name'=>'ChannelAdvisor',
                'active'=>true,
                'price'=>true,
                'stock'=>false,
                'product'=>false,
                'order'=>true,
            ],
            [
                'code'=>IntegrationChannel::CHANNEL_ALIEXPRESS, 
                'name'=>'AliExpress (Gadget Iberia)',
                'active'=>true,
                'price'=>false,
                'stock'=>true,
                'product'=>false,
                'order'=>true,
            ],
           
            [
                'code'=>IntegrationChannel::CHANNEL_ARISE, 
                'name'=>'Arise',
                'active'=>false,
                'price'=>false,
                'stock'=>true,
                'product'=>false,
                'order'=>true,
            ],
            [
                'code'=>IntegrationChannel::CHANNEL_FITBITCORPORATE, 
                'name'=>'Google & Fitbit',
                'active'=>false,
                'price'=>false,
                'stock'=>true,
                'product'=>false,
                'order'=>true,
            ],
            [
                'code'=>IntegrationChannel::CHANNEL_FITBITEXPRESS, 
                'name'=>'AliExpress (Fitbit)',
                'active'=>true,
                'price'=>false,
                'stock'=>true,
                'product'=>false,
                'order'=>true,
            ],
            [
                'code'=>IntegrationChannel::CHANNEL_FLASHLED, 
                'name'=>'Flashled',
                'active'=>true,
                'price'=>false,
                'stock'=>true,
                'product'=>false,
                'order'=>true,
            ],
            [
                'code'=>IntegrationChannel::CHANNEL_MINIBATT, 
                'name'=>'Minibatt',
                'active'=>true,
                'price'=>false,
                'stock'=>true,
                'product'=>false,
                'order'=>true,
            ],
            [
                'code'=>IntegrationChannel::CHANNEL_OWLETCARE, 
                'name'=>'Minibatt',
                'active'=>true,
                'price'=>false,
                'stock'=>true,
                'product'=>false,
                'order'=>true,
            ],         
        ];

        foreach ($integrationChannels as $integrationChannelArr) {
            $integrationChannelDv = $this->manager->getRepository(IntegrationChannel::class)->findOneByCode($integrationChannelArr['code']);
            if (!$integrationChannelDv) {
                $integrationChannel = new IntegrationChannel();
                $integrationChannel->setCode($integrationChannelArr['code']);
                $integrationChannel->setName($integrationChannelArr['name']);
                $integrationChannel->setActive($integrationChannelArr['active']);
                $integrationChannel->setPriceSync($integrationChannelArr['price']);
                $integrationChannel->setStockSync($integrationChannelArr['stock']);
                $integrationChannel->setProductSync($integrationChannelArr['product']);
                $integrationChannel->setOrderSync($integrationChannelArr['order']);
                        
                $this->manager->persist($integrationChannel);
            }
           
        }
        $this->manager->flush();


    
        return Command::SUCCESS;
    }
}
