<?php

namespace App\Command\Pricing;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Entity\IntegrationChannel;
use App\Entity\Product;
use App\Entity\ProductSaleChannel;
use App\Entity\SaleChannel;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateSaleChannelsCommand extends Command
{
    protected static $defaultName = 'app:create-sale-channels';
    protected static $defaultDescription = 'Import sale channels';

    public function __construct(ManagerRegistry $manager)
    {
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    private $manager;

    private $akeneoConnector;




    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $marketplaces=[
            ["channel"=>IntegrationChannel::CHANNEL_ALIEXPRESS, 'code'=>'aliexpress_es_gi', 'name'=>'Aliexpress.es Gadget Iberia', 'countryCode'=>'ES', 'currencyCode'=>'EUR', 'company'=> BusinessCentralConnector::GADGET_IBERIA],
            ["channel"=>IntegrationChannel::CHANNEL_FITBITEXPRESS, 'code'=>'fitbitexpress_es_gi', 'name'=>'Fitbit on Aliexpress.es Gadget Iberia', 'countryCode'=>'ES', 'currencyCode'=>'EUR', 'company'=> BusinessCentralConnector::GADGET_IBERIA],
        ];
        /** @var \App\Entity\Product[] */
        $products= $this->manager->getRepository(Product::class)->findAll();

        foreach ($marketplaces as $marketplace) {
            $saleChannelDb = $this->manager->getRepository(SaleChannel::class)->findOneByCode($marketplace['code']);
            if (!$saleChannelDb) {
                $saleChannel = new SaleChannel();
                $saleChannel->setCode($marketplace['code']);
                $saleChannel->setName($marketplace['name']);
                $saleChannel->setCountryCode($marketplace['countryCode']);
                $saleChannel->setCurrencyCode($marketplace['currencyCode']);
                $saleChannel->setCompany($marketplace['company']);
                $saleChannel->setColor($marketplace['color']);
                $integrationChannelDb = $this->manager->getRepository(IntegrationChannel::class)->findOneByCode($marketplace['channel']);
                $saleChannel->setChannel($integrationChannelDb);

                foreach ($products as $product) {
                    $productSaleChannel = new ProductSaleChannel();
                    $productSaleChannel->setProduct($product);
                    $saleChannel->addProductSaleChannel($productSaleChannel);
                }
                        
                        
                $this->manager->persist($saleChannel);
                $this->manager->flush();
            }
        }
        


    
        return Command::SUCCESS;
    }
}
