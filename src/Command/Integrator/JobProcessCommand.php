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
        $output->writeln('Start processing');
        $manager = $this->managerRegistry->getManager();
        $jobs = $manager->getRepository(Job::class)->findByStatus(Job::Status_Processing);
        if(count($jobs)>0) {
            $output->writeln('Already processing');
            foreach($jobs as $job){
                if($job->getExecutionTime()>600){
                    $job->setStatus(Job::Status_Error);
                    $job->setEndDate(new DateTime());
                }
            }
            return Command::SUCCESS;
        } else {
            $output->writeln('No job running');
        }

        $jobToProcesss = $manager->getRepository(Job::class)->findByStatus(Job::Status_Created);
        $output->writeln('Nb jobs '.count($jobToProcesss));
        if(count($jobToProcesss)>0) {
            foreach($jobToProcesss as $jobToProcess) {
                $jobToProcess->setStatus(Job::Status_Processing);
            }
            $manager->flush();
            foreach($jobToProcesss as $jobToProcess) {
                $jobToProcess->setStartDate(new DateTime());
                if($jobToProcess->getJobType()==Job::Type_Sync_Products) {
                    try {
                        $productUpdater = $this->productSyncAggregator->getProductSync($jobToProcess->getChannel()->getCode());
                        $productUpdater->syncProducts();
                        $jobToProcess->setStatus(Job::Status_Finished);
                    } catch (Exception $e) {
                        $jobToProcess->setStatus(Job::Status_Error);
                    }
                    $jobToProcess->setEndDate(new DateTime());
                   
                    $manager->flush();
                }
            }
        } else {
            $output->writeln('No jobs');
        }
        
        return Command::SUCCESS;
    }
}
