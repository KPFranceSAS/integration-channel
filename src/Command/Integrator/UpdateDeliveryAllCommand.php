<?php

namespace App\Command\Integrator;

use App\Entity\IntegrationChannel;
use App\Helper\MailService;
use App\Service\Aggregator\UpdateDeliveryAggregator;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateDeliveryAllCommand extends Command
{
    protected static $defaultName = 'app:update-delivery-orders-all';
    protected static $defaultDescription = 'Update all status of deliveries from all sale channels';

    public function __construct(private readonly UpdateDeliveryAggregator $deliveryAggregator, ManagerRegistry $managerRegistry, private readonly LoggerInterface $logger, private readonly MailService $mailService)
    {
        $this->managerRegistry = $managerRegistry->getManager();
        parent::__construct();
    }

    private $managerRegistry;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dateTime = new DateTime();
        $dateTimeFormat = $dateTime->format('H:i');
        
        if ($dateTimeFormat < '06:30' && $dateTimeFormat > '02:00') {
            $this->logger->info('Out of service '.$dateTimeFormat);
            return Command::SUCCESS;
        }

        $channelCodes = $this->managerRegistry->getRepository(IntegrationChannel::class)->findBy(
            [
                "active"=>true,
                "orderSync"=>true,
            ]
        );

        $channels=[];
        foreach ($channelCodes as $channelCode) {
            $channels[]=$channelCode->getCode();
        }



        foreach ($channels as $channel) {
            try {
                $this->logger->info('');
                $this->logger->info('##########################################');
                $this->logger->info('Start update status CHANNEL >>> '.$channel);
                $this->logger->info('##########################################');
                $this->logger->info('');
                $integrator = $this->deliveryAggregator->getDelivery($channel);
                $integrator->updateStatusDeliveries();
                $this->logger->info('');
                $this->logger->info('##########################################');
                $this->logger->info('End update status CHANNEL >>> '.$channel);
                $this->logger->info('##########################################');
                $this->logger->info('');
                $this->managerRegistry->clear();
            } catch (Exception $e) {
                $this->mailService->sendEmail('Error in UpdateDeliveryAllCommand '.$channel, $e->getMessage());
            }
        }

        
        return Command::SUCCESS;
    }
}
