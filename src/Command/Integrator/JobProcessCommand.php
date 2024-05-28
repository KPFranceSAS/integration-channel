<?php

namespace App\Command\Integrator;

use App\Entity\Job;
use App\Service\Aggregator\PriceAggregator;
use App\Service\Aggregator\PriceStockAggregator;
use App\Service\Aggregator\ProductSyncAggregator;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:job-process', 'Job Process')]
class JobProcessCommand extends Command
{
    public function __construct(
        private readonly ProductSyncAggregator $productSyncAggregator,
        private readonly PriceAggregator $priceAggregator,
        private readonly PriceStockAggregator $priceStockAggregator,
        private readonly ManagerRegistry $managerRegistry,
    ) {
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $manager = $this->managerRegistry->getManager();
        $jobs = $manager->getRepository(Job::class)->findByStatus(Job::Status_Processing);
        if(count($jobs)>0) {
            $output->writeln('Already processing');
            return Command::SUCCESS;
        }

        $jobToProcesss = $manager->getRepository(Job::class)->findByStatus(Job::Status_Created);
        if(count($jobToProcesss)>0) {
            foreach($jobs as $job) {
                $job->setStatus(Job::Status_Processing);
            }
            $manager->flush();
            foreach($jobs as $job) {
                $job->setStartDate(new DateTime());
                if($job->getJobType()==Job::Type_Sync_Products) {
                    try {
                        $productUpdater = $this->productSyncAggregator->getProductSync($job->getChannel()->getCode());
                        $productUpdater->syncProducts();
                    } catch (Exception $e) {
                        
                    }
                    $job->setEndDate(new DateTime());
                    $job->setStatus(Job::Status_Finished);
                    $manager->flush();
                }
            }
        } else {
            $output->writeln('No jobs');
        }
        
        return Command::SUCCESS;
    }
}
