<?php

namespace App\Command\Pricing;

use App\Entity\Product;
use App\Entity\ProductSaleChannel;
use App\Entity\SaleChannel;
use App\Entity\WebOrder;
use App\BusinessCentral\Connector\BusinessCentralConnector;
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
        $amrektplaces=[
            ['code'=>'amazon_es_kp', 'name'=>'Amazon.es KP France', 'countryCode'=>'ES', 'currencyCode'=>'EUR', 'company'=> BusinessCentralConnector::KP_FRANCE, 'color' => '#F72585'],
            ['code'=>'amazon_de_kp', 'name'=>'Amazon.de KP France', 'countryCode'=>'DE', 'currencyCode'=>'EUR', 'company'=> BusinessCentralConnector::KP_FRANCE, 'color' => '#370031'],
            ['code'=>'amazon_it_kp', 'name'=>'Amazon.it KP France', 'countryCode'=>'IT', 'currencyCode'=>'EUR', 'company'=> BusinessCentralConnector::KP_FRANCE, 'color' => '#832232'],
            ['code'=>'amazon_uk_kp', 'name'=>'Amazon.uk KP France', 'countryCode'=>'GB', 'currencyCode'=>'GBP', 'company'=> BusinessCentralConnector::KP_FRANCE, 'color' => '#CE8964'],
            ['code'=>'amazon_fr_kp', 'name'=>'Amazon.fr KP France', 'countryCode'=>'FR', 'currencyCode'=>'EUR', 'company'=> BusinessCentralConnector::KP_FRANCE, 'color' => '#EAF27C'],
            ['code'=>'amazon_es_gi', 'name'=>'Amazon.es Gadget Iberia', 'countryCode'=>'ES', 'currencyCode'=>'EUR', 'company'=> BusinessCentralConnector::GADGET_IBERIA, 'color' => '#4CC9F0'],

            /*['code'=>'amazon_fr_gi', 'name'=>'Amazon.fr Gadget Iberia', 'countryCode'=>'FR', 'currencyCode'=>'EUR', 'company'=> BusinessCentralConnector::GADGET_IBERIA],
            ['code'=>'amazon_de_gi', 'name'=>'Amazon.de Gadget Iberia', 'countryCode'=>'DE', 'currencyCode'=>'EUR', 'company'=> BusinessCentralConnector::GADGET_IBERIA],
            ['code'=>'amazon_it_gi', 'name'=>'Amazon.it Gadget Iberia', 'countryCode'=>'IT', 'currencyCode'=>'EUR', 'company'=> BusinessCentralConnector::GADGET_IBERIA],
            ['code'=>'amazon_uk_gi', 'name'=>'Amazon.uk Gadget Iberia', 'countryCode'=>'GB', 'currencyCode'=>'GBP', 'company'=> BusinessCentralConnector::GADGET_IBERIA],
            ['code'=>'aliexpress_es_gi', 'name'=>'Aliexpress.es Gadget Iberia', 'countryCode'=>'ES', 'currencyCode'=>'EUR', 'company'=> BusinessCentralConnector::GADGET_IBERIA],*/
        ];
       
        $products= $this->manager->getRepository(Product::class)->findAll();

        foreach ($amrektplaces as $marketplace) {
            $saleChannel = new SaleChannel();
            $saleChannel->setCode($marketplace['code']);
            $saleChannel->setName($marketplace['name']);
            $saleChannel->setCountryCode($marketplace['countryCode']);
            $saleChannel->setCurrencyCode($marketplace['currencyCode']);
            $saleChannel->setCompany($marketplace['company']);
            $saleChannel->setColor($marketplace['color']);
            $saleChannel->setChannel(WebOrder::CHANNEL_CHANNELADVISOR);

                foreach ($products as $product) {
                    $productSaleChannel = new ProductSaleChannel();
                    $productSaleChannel->setProduct($product);
                    $saleChannel->addProductSaleChannel($productSaleChannel);
                }
               
            
            $this->manager->persist($saleChannel);
            $this->manager->flush();
            
        }
        


    
        return Command::SUCCESS;
    }
}
