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

class OrderIntegrateAllCommand extends Command
{
    protected static $defaultName = 'app:integrate-orders-all';
    protected static $defaultDescription = 'Integrates all orders from all sale channels';

    public function __construct(IntegratorAggregator $integrateAggregator, ManagerRegistry $managerRegistry, LoggerInterface $logger, MailService $mailService)
    {
        $this->integrateAggregator = $integrateAggregator;
        $this->logger = $logger;
        $this->mailService = $mailService;
        $this->managerRegistry = $managerRegistry->getManager();
        parent::__construct();
    }

    private $managerRegistry;

    private $integrateAggregator;

    private $logger;

    private $mailService;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
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


        $channels = $this->managerRegistry->getRepository(IntegrationChannel::class)->findBy(
            [
                "active"=>true,
                "orderSync"=>true,
            ]
        );
        foreach ($channels as $channel) {
            try {
                $this->logger->info('');
                $this->logger->info('##########################################');
                $this->logger->info('Start integration CHANNEL >>> '.$channel->getCode());
                $this->logger->info('##########################################');
                $this->logger->info('');
                $integrator = $this->integrateAggregator->getIntegrator($channel->getCode());
                $retryIntegration = boolval($input->getArgument('retryIntegration'));
                $integrator->processOrders($retryIntegration);
                $this->logger->info('');
                $this->logger->info('##########################################');
                $this->logger->info('End integration CHANNEL >>> '.$channel->getCode());
                $this->logger->info('##########################################');
                $this->logger->info('');
            } catch (Exception $e) {
                $this->mailService->sendEmail('Error in OrderIntegrateChannelCommand', $e->getMessage());
            }
        }

        
        return Command::SUCCESS;
    }
}
