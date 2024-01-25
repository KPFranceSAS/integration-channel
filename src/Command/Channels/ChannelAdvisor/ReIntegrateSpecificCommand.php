<?php

namespace App\Command\Channels\ChannelAdvisor;

use App\Channels\ChannelAdvisor\ChannelAdvisorApi;
use App\Channels\ChannelAdvisor\ChannelAdvisorIntegrateOrder;
use App\Entity\AmazonOrder;
use App\Entity\WebOrder;
use App\Helper\Utils\CsvExtracter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReIntegrateSpecificCommand extends Command
{
    protected static $defaultName = 'app:channel-reintegrate-command';
    protected static $defaultDescription = 'Reintegrate command from ChannelAdvisor';

    public function __construct(ManagerRegistry $managerRegistry, private readonly ChannelAdvisorApi $channelAdvisorApi, private readonly ChannelAdvisorIntegrateOrder $channelAdvisorIntegrateOrder, private readonly CsvExtracter $csvExtracter)
    {
        $this->managerRegistry= $managerRegistry->getManager();
        parent::__construct();
    }

    private $managerRegistry;
   
    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('filePath', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orders = $this->csvExtracter->extractAssociativeDatasFromCsv($input->getArgument('filePath'));

        foreach ($orders as $order) {
            $orderNumber = $order['OrderNumber'];
            $output->writeln('Order Amz '.$orderNumber);
            
            $webOrder = $this->managerRegistry->getRepository(WebOrder::class)->findOneBy(['externalNumber'=>$orderNumber]);
            if ($webOrder) {
                $output->writeln('Delete weborder '.$webOrder->getId());
                $this->managerRegistry->remove($webOrder);
            }

            $amzOrder = $this->managerRegistry->getRepository(AmazonOrder::class)->findBy(['amazonOrderId'=>$orderNumber]);
            if (count($amzOrder)>0) {
                foreach ($amzOrder as $amzOrde) {
                    $output->writeln('Update amz order '.$amzOrde->getId());
                    $amzOrde->setIntegrated(false);
                    $amzOrde->setIntegrationNumber(null);
                }
            }
            $this->managerRegistry->flush();

            $orderApi = $this->channelAdvisorApi->getOrderByNumber($orderNumber);
            if ($orderApi) {
                $this->channelAdvisorIntegrateOrder->integrateOrder($orderApi);
            }
        }
        return Command::SUCCESS;
    }
}
