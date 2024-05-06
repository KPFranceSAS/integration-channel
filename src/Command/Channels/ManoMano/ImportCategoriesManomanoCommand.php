<?php

namespace App\Command\Channels\ManoMano;

use App\Channels\ManoMano\ManoManoFr\ManoManoFrApi;
use App\Entity\MarketplaceCategory;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:import-categories-manomano', 'Connection to manomano and import categories')]
class ImportCategoriesManomanoCommand extends Command
{
    public function __construct(
        private readonly ManoManoFrApi $manoManoFrApi,
        private readonly ManagerRegistry $managerRegistry,

    ) {
        parent::__construct();
        $this->manager = $this->managerRegistry->getManager();
    }


    private $manager;
  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $indexedCategories =[];
        $marketplaceCategories = $this->manager->getRepository(MarketplaceCategory::class)->findByMarketplace('manomano');
        foreach($marketplaceCategories as $marketplaceCAtgeory){
            $indexedCategories[$marketplaceCAtgeory->getCode()] = $marketplaceCAtgeory;
        }
        

        $categoriesMirakl = $this->manoManoFrApi->getCategorieChoices();

        foreach ($categoriesMirakl as $categoryMirakl) {
            if(!array_key_exists($categoryMirakl['code'], $indexedCategories)) {
                $productTypeCat=new MarketplaceCategory();
                $productTypeCat->setCode($categoryMirakl['code']);
                $productTypeCat->setMarketplace('manomano');
                $this->manager->persist($productTypeCat);
            }  else {
                $productTypeCat = $indexedCategories[$categoryMirakl['code']];
            }

            $productTypeCat->setLabel($categoryMirakl['label']);
            $productTypeCat->setPath($categoryMirakl['path']);
        }

        $this->manager->flush();
        $this->manager->clear();


        return Command::SUCCESS;
    }





}
