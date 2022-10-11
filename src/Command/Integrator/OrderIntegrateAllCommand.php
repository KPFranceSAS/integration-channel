<?php

namespace App\Command\Integrator;

use App\Helper\MailService;
use App\Service\Aggregator\IntegratorAggregator;
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

    public function __construct(IntegratorAggregator $integrateAggregator, LoggerInterface $logger, MailService $mailService)
    {
        $this->integrateAggregator = $integrateAggregator;
        $this->logger = $logger;
        $this->mailService = $mailService;
        parent::__construct();
    }

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
        $channels = $this->integrateAggregator->getChannels();
        foreach ($channels as $channel) {
            try {
                $this->logger->info('<<< ------------------------------ >>> ');
                $this->logger->info('Start integration CHANNEL >>> '.$channel);
                $integrator = $this->integrateAggregator->getIntegrator($channel);
                $retryIntegration = boolval($input->getArgument('retryIntegration'));
                $integrator->processOrders($retryIntegration);
                $this->logger->info('End integration CHANNEL >>> '.$channel);
                $this->logger->info('<<< ------------------------------ >>> ');
            } catch (Exception $e) {
                $this->mailService->sendEmail('Error in OrderIntegrateChannelCommand', $e->getMessage());
            }
        }

        
        return Command::SUCCESS;
    }
}
