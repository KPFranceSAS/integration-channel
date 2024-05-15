<?php

namespace App\Command\Channels\ChannelAdvisor;


use App\Entity\MarketplaceCategory;
use App\Helper\Utils\CsvExtracter;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:import-categories-amazon', 'Connection to mirakl and import categories')]
class ImportCategoriesAmazonCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly CsvExtracter $csvExtracter
    ) {
        parent::__construct();
        $this->manager = $this->managerRegistry->getManager();
    }


    private $manager;


  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channels = ['amazonFr', 'amazonEs', 'amazonIt', 'amazonDe', 'amazonUk'];
        foreach($channels as $channel){
            $this->manageChannel($output, $channel);
        }
        

        
        return Command::SUCCESS;
    }

    
    protected function manageChannel($output, $channel){

        $categoriesMirakl = $this->csvExtracter->extractAssociativeDatasFromCsv('docs/amazon/'.$channel.'.csv');
        $output->writeln('Found '.count($categoriesMirakl).' categories');

        $indexedCategories =[];
        $marketplaceCategories = $this->manager->getRepository(MarketplaceCategory::class)->findByMarketplace($channel);
        foreach($marketplaceCategories as $marketplaceCAtgeory) {
            $indexedCategories[$marketplaceCAtgeory->getCode()] = $marketplaceCAtgeory;
        }

        $output->writeln('Found in db '.count($marketplaceCategories).' categories');
     

        foreach ($categoriesMirakl as $categoryMirakl) {
            if(!array_key_exists($categoryMirakl['Node ID'], $indexedCategories)) {
                $productTypeCat=new MarketplaceCategory();
                $productTypeCat->setCode($categoryMirakl['Node ID']);
                $productTypeCat->setMarketplace($channel);
                $this->manager->persist($productTypeCat);
                $indexedCategories[$categoryMirakl['Node ID']] = $productTypeCat;
            } else {
                $productTypeCat = $indexedCategories[$categoryMirakl['Node ID']];
            }

            $nodePath  = explode("/", $categoryMirakl['Node Path']);
            $label = end($nodePath);
            $productTypeCat->setLabel($label);
            $productTypeCat->setPath($categoryMirakl['Node Path']);
        }

        $this->manager->flush();
        $this->manager->clear();

    }



}
