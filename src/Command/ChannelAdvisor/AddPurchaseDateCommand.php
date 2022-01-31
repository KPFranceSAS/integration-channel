<?php

namespace App\Command\ChannelAdvisor;

use App\Entity\AmazonOrder;
use App\Entity\IntegrationFile;
use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Service\BusinessCentral\KpFranceConnector;
use App\Service\ChannelAdvisor\ChannelWebservice;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use stdClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddPurchaseDateCommand extends Command
{
    protected static $defaultName = 'app:channel-add-purchase-dates';
    protected static $defaultDescription = 'Add purchase dates';

    public function __construct(KpFranceConnector $saleOrderConnector, ChannelWebservice $channelWebservice, ManagerRegistry $manager)
    {
        $this->manager = $manager->getManager();
        parent::__construct();
    }


    private $manager;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $counter = 0;
        $webOrders = $this->manager->getRepository(WebOrder::class)->findBy(["purchaseDate" => null, 'channel' => WebOrder::CHANNEL_CHANNELADVISOR]);

        foreach ($webOrders as $webOrder) {
            $orderApi = $webOrder->getOrderContent();

            $webOrder->setPurchaseDateFromString($orderApi->PaymentDateUtc);
        }
        $this->manager->flush();


        return Command::SUCCESS;
    }
}
