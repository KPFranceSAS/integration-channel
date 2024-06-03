<?php

namespace App\Command\Channels\Arise;

use App\Entity\MarketplaceCategory;
use App\Helper\Utils\CsvExtracter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:import-categories-miravia', 'Import categories form miravia files')]
class ImportCategoriesMiraviaCommand extends Command
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
        
        $categoriesMirakl = $this->csvExtracter->extractAssociativeDatasFromCsv('docs/miravia/categories.csv',",");
        $output->writeln('Found '.count($categoriesMirakl).' categories');

        $indexedCategories =[];
        $marketplaceCategories = $this->manager->getRepository(MarketplaceCategory::class)->findByMarketplace('miravia');
        foreach($marketplaceCategories as $marketplaceCAtgeory) {
            $indexedCategories[$marketplaceCAtgeory->getCode()] = $marketplaceCAtgeory;
        }

        $output->writeln('Found in db '.count($marketplaceCategories).' categories');

        foreach ($categoriesMirakl as $categoryMirakl) {
            if(!array_key_exists($categoryMirakl['Leaf Category ID'], $indexedCategories)) {
                $productTypeCat=new MarketplaceCategory();
                $productTypeCat->setCode($categoryMirakl['Leaf Category ID']);
                $productTypeCat->setMarketplace('miravia');
                $this->manager->persist($productTypeCat);
                $indexedCategories[$categoryMirakl['Leaf Category ID']] = $productTypeCat;
            } else {
                $productTypeCat = $indexedCategories[$categoryMirakl['Leaf Category ID']];
            }

            $paths=[];
            for($i=1;$i<=6;$i++){
                if(strlen($categoryMirakl['Level_'.$i.'_Category_Name'])>0){
                    $paths[]=$categoryMirakl['Level_'.$i.'_Category_Name'];
                }
            }


            $path = implode(' > ', $paths).' > '. $categoryMirakl['Leaf Category Name'];
            $label = $categoryMirakl['Leaf Category Name'];

            $productTypeCat->setLabel($label);
            $productTypeCat->setPath($path);
        }

        $this->manager->flush();
        $this->manager->clear();
        

        
        return Command::SUCCESS;
    }



}
