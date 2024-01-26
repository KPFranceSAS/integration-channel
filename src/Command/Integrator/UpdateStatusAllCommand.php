<?php

namespace App\Command\Integrator;

use App\Entity\IntegrationChannel;
use App\Helper\MailService;
use App\Service\Aggregator\UpdateStatusAggregator;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:update-status-orders-all', 'Update all status of orders from all sale channels')]
class UpdateStatusAllCommand extends Command
{
    public function __construct(private readonly UpdateStatusAggregator $invoiceAggregator, ManagerRegistry $managerRegistry, private readonly LoggerInterface $logger, private readonly MailService $mailService)
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
                $this->logger->info('Start update status CHANNEL >>> '.$channel);
                $this->logger->info('##########################################');
                $this->logger->info('');
                $integrator = $this->invoiceAggregator->getInvoice($channel);
                $retryIntegration = boolval($input->getArgument('retryIntegration'));
                $integrator->updateStatusSales($retryIntegration);
                $this->logger->info('');
                $this->logger->info('##########################################');
                $this->logger->info('End update status CHANNEL >>> '.$channel);
                $this->logger->info('##########################################');
                $this->logger->info('');
                $this->managerRegistry->clear();
            } catch (Exception $e) {
                $this->mailService->sendEmail('Error in InvoiceIntegrateAllCommand '.$channel, $e->getMessage());
            }
        }

        
        return Command::SUCCESS;
    }
}
