<?php

namespace App\Command\Channels\ChannelAdvisor;

use App\Entity\MarketplaceCategory;
use App\Helper\Utils\CsvExtracter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:import-categories-cdiscount', 'Import categories form cdiscoutn files')]
class ImportCategoriesCdiscountCommand extends Command
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
        
        $categoriesMirakl = $this->csvExtracter->extractAssociativeDatasFromCsv('docs/cdiscount/categories.csv');
        $output->writeln('Found '.count($categoriesMirakl).' categories');

        $indexedCategories =[];
        $marketplaceCategories = $this->manager->getRepository(MarketplaceCategory::class)->findByMarketplace('cdiscount');
        foreach($marketplaceCategories as $marketplaceCAtgeory) {
            $indexedCategories[$marketplaceCAtgeory->getCode()] = $marketplaceCAtgeory;
        }

        $output->writeln('Found in db '.count($marketplaceCategories).' categories');
     

        foreach ($categoriesMirakl as $categoryMirakl) {
            if(!array_key_exists($categoryMirakl['Code'], $indexedCategories)) {
                $productTypeCat=new MarketplaceCategory();
                $productTypeCat->setCode($categoryMirakl['Code']);
                $productTypeCat->setMarketplace('cdiscount');
                $this->manager->persist($productTypeCat);
                $indexedCategories[$categoryMirakl['Code']] = $productTypeCat;
            } else {
                $productTypeCat = $indexedCategories[$categoryMirakl['Code']];
            }

            $path = $categoryMirakl['Category level 1'] . ' > '. $categoryMirakl['Category level 2'] . ' > '. $categoryMirakl['Category level 3'] . ' > '. $categoryMirakl['Category level 4'];
            $label = $categoryMirakl['Category level 4'];

            $productTypeCat->setLabel($label);
            $productTypeCat->setPath($path);
        }

        $this->manager->flush();
        $this->manager->clear();
        

        
        return Command::SUCCESS;
    }



}
