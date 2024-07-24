<?php

namespace App\Command\Channels\Mirakl;

use App\Channels\FnacDarty\FnacFr\FnacFrApi;
use App\Channels\Mirakl\Boulanger\BoulangerApi;
use App\Channels\Mirakl\Decathlon\DecathlonApi;
use App\Channels\Mirakl\LeroyMerlin\LeroyMerlinApi;
use App\Channels\Mirakl\MediaMarkt\MediaMarktApi;
use App\Channels\Mirakl\PcComponentes\PcComponentesApi;
use App\Channels\Mirakl\Worten\WortenApi;
use App\Entity\MarketplaceCategory;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:import-categories-mirakl', 'Connection to mirakl and import categories')]
class ImportCategoriesMiraklCommand extends Command
{
    public function __construct(
        private readonly DecathlonApi $decathlonApi,
        private readonly FnacFrApi $fnacDartyApi,
        private readonly BoulangerApi $boulangerApi,
        private readonly LeroyMerlinApi $leroymerlinApi,
        private readonly MediaMarktApi $mediamarktApi,
        private readonly WortenApi $wortenApi,
        private readonly PcComponentesApi $pcComponentesApi,
        private readonly ManagerRegistry $managerRegistry,
    ) {
        parent::__construct();
        $this->manager = $this->managerRegistry->getManager();
    }


    private $manager;
  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $channels =  ['decathlon', 'fnacDarty', 'boulanger', 'leroymerlin', 'mediamarkt', 'worten', 'pcComponentes'];

        foreach($channels as $channel) {
            $output->writeln('Start '.$channel);

            $this->manageChannel($channel);
        }

        return Command::SUCCESS;
    }

    public function manageChannel($channel)
    {
        $indexedCategories =[];
        $marketplaceCategories = $this->manager->getRepository(MarketplaceCategory::class)->findByMarketplace($channel);
        foreach($marketplaceCategories as $marketplaceCAtgeory) {
            $indexedCategories[$marketplaceCAtgeory->getCode()] = $marketplaceCAtgeory;
        }
        

        $categoriesMirakl = $this->{$channel.'Api'}->getCategorieChoices();

        foreach ($categoriesMirakl as $categoryMirakl) {
            if(!array_key_exists($categoryMirakl['code'], $indexedCategories)) {
                $productTypeCat=new MarketplaceCategory();
                $productTypeCat->setCode($categoryMirakl['code']);
                $productTypeCat->setMarketplace($channel);
                $this->manager->persist($productTypeCat);
            } else {
                $productTypeCat = $indexedCategories[$categoryMirakl['code']];
            }

            $productTypeCat->setLabel($categoryMirakl['label']);
            $productTypeCat->setPath($categoryMirakl['path']);
        }

        $this->manager->flush();
        $this->manager->clear();





    }





}
