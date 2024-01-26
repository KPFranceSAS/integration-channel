<?php

namespace App\Command\Integrator;

use App\Entity\IntegrationChannel;
use App\Helper\MailService;
use App\Service\Aggregator\IntegratorAggregator;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:integrate-orders-all', 'Integrates all orders from all sale channels')]
class OrderIntegrateAllCommand extends Command
{
    public function __construct(private readonly IntegratorAggregator $integrateAggregator, ManagerRegistry $managerRegistry, private readonly LoggerInterface $logger, private readonly MailService $mailService)
    {
        $this->managerRegistry = $managerRegistry->getManager();
        parent::__construct();
    }

    private $managerRegistry;


    protected function configure(): void
    {
        $this
            
            ->addArgument('retryIntegration', InputArgument::OPTIONAL, 'To reimport all errors add 1', false);
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
                $this->logger->info('Start integration CHANNEL >>> '.$channel);
                $this->logger->info('##########################################');
                $this->logger->info('');
                $integrator = $this->integrateAggregator->getIntegrator($channel);
                $retryIntegration = boolval($input->getArgument('retryIntegration'));
                $integrator->processOrders($retryIntegration);
                $this->logger->info('');
                $this->logger->info('##########################################');
                $this->logger->info('End integration CHANNEL >>> '.$channel);
                $this->logger->info('##########################################');
                $this->logger->info('');
                $this->managerRegistry->clear();
            } catch (Exception $e) {
                $this->mailService->sendEmail('Error in OrderIntegrateChannelCommand '.$channel, $e->getMessage());
            }
        }

        
        return Command::SUCCESS;
    }
}
