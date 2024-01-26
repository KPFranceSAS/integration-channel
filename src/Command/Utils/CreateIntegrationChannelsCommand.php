<?php

namespace App\Command\Utils;

use App\Entity\IntegrationChannel;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:create-integration-channels', 'Import integration channels')]
class CreateIntegrationChannelsCommand extends Command
{
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
                'code'=>IntegrationChannel::CHANNEL_LEROYMERLIN,
                'name'=>'Leroy Merlin',
                'active'=>false,
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
