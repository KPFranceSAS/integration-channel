<?php

namespace App\Command\Integrator;

use App\Entity\IntegrationChannel;
use App\Helper\MailService;
use App\Service\Aggregator\InvoiceAggregator;
use App\Service\Aggregator\UpdateStatusAggregator;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateStatusAllCommand extends Command
{
    protected static $defaultName = 'app:update-status-orders-all';
    protected static $defaultDescription = 'Update all status of orders from all sale channels';

    public function __construct(UpdateStatusAggregator $invoiceAggregator,ManagerRegistry $managerRegistry,  LoggerInterface $logger, MailService $mailService)
    {
        $this->invoiceAggregator = $invoiceAggregator;
        $this->logger = $logger;
        $this->mailService = $mailService;
        $this->managerRegistry = $managerRegistry->getManager();
        parent::__construct();
    }

    private $invoiceAggregator;

    private $managerRegistry;

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
                $this->logger->info('Start update status CHANNEL >>> '.$channel->getCode());
                $this->logger->info('##########################################');
                $this->logger->info('');
                $integrator = $this->invoiceAggregator->getInvoice($channel->getCode());
                $retryIntegration = boolval($input->getArgument('retryIntegration'));
                $integrator->updateStatusSales($retryIntegration);
                $this->logger->info('');
                $this->logger->info('##########################################');
                $this->logger->info('End update status CHANNEL >>> '.$channel->getCode());
                $this->logger->info('##########################################');
                $this->logger->info('');
            } catch (Exception $e) {
                $this->mailService->sendEmail('Error in InvoiceIntegrateAllCommand', $e->getMessage());
            }
        }

        
        return Command::SUCCESS;
    }
}
