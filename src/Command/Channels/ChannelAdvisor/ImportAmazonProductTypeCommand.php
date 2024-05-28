<?php

namespace App\Command\Channels\ChannelAdvisor;

use App\Entity\AmazonProductType;
use App\Entity\MarketplaceCategory;
use App\Helper\Utils\CsvExtracter;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:import-type-amazon', 'Import amazon type')]
class ImportAmazonProductTypeCommand extends Command
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
        
        $categoriesMirakl = $this->csvExtracter->extractAssociativeDatasFromCsv('docs/amazon/amazonProductType.csv');
        $output->writeln('Found '.count($categoriesMirakl).' categories');

        $indexedCategories =[];
        $marketplaceCategories = $this->manager->getRepository(AmazonProductType::class)->findAll();
        foreach($marketplaceCategories as $marketplaceCAtgeory) {
            $indexedCategories[$marketplaceCAtgeory->getLabel()] = $marketplaceCAtgeory;
        }

        $output->writeln('Found in db '.count($marketplaceCategories).' categories');
     


        foreach ($categoriesMirakl as $categoryMirakl) {
            $key = $categoryMirakl['General category'].' > '.$categoryMirakl['Product Type'];
            if(!array_key_exists($key, $indexedCategories)) {

                $productTypeCat=new AmazonProductType();
                $productTypeCat->setCode($categoryMirakl['Product Type']);
                $productTypeCat->setLabel($key);
                $this->manager->persist($productTypeCat);
                $indexedCategories[$key] = $productTypeCat;
            } 

            
        }

        $this->manager->flush();
        $this->manager->clear();

        

        
        return Command::SUCCESS;
    }





}
