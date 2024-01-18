<?php

namespace App\Command\Pim;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\BusinessCentral\LogisticClassFinder;
use App\Entity\Brand;
use App\Entity\IntegrationChannel;
use App\Entity\Product;
use App\Entity\ProductSaleChannel;
use App\Entity\SaleChannel;
use App\Helper\MailService;
use App\Service\Pim\AkeneoConnector;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MarketplaceAssignementCommand extends Command
{
    protected static $defaultName = 'app:sync-marketplace-assignments';
    protected static $defaultDescription = 'Add marketplace assignments';

    public function __construct(
        ManagerRegistry $manager,
        AkeneoConnector $akeneoConnector
    ) {
        $this->manager = $manager->getManager();
        $this->akeneoConnector = $akeneoConnector;
        parent::__construct();
    }

    private $manager;

    private $akeneoConnector;




    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $productToArrays=[];
        

        



        $products= $this->akeneoConnector->getAllProducts();
        foreach ($products as $product) {
            $productDb = $this->manager->getRepository(Product::class)->findOneBySku($product['identifier']);

            if($productDb && $productDb->isEnabled()) {
                $updatePim = [];
                $productEnabledOnMarketPlace = $this->getAttributeSimpleScopable($product, 'enabled_channel', 'Marketplace');
                if($productEnabledOnMarketPlace!=true) {
                    $updatePim['enabled_channel']=[
                        [
                            'data' => true,
                            'scope'=>  'Marketplace',
                            'locale' => null
                        ]
                        ];
                }

                $productAssignation = $this->getAttributeSimpleScopable($product, 'marketplaces_assignement');

                if(!$productAssignation || ($productAssignation && !in_array('bigbuy_es_kps', $productAssignation))) {
                    if(!$productAssignation) {
                        $productAssignation=[];
                    }
                    $productAssignation[]='bigbuy_es_kps';
                    $updatePim['marketplaces_assignement']=[
                        [
                            'data' => $productAssignation,
                            'scope'=>  null,
                            'locale' => null
                        ]
                        ];
                }

                if(count($updatePim)>0) {
                    $this->akeneoConnector->updateProductParent($product['identifier'], $product['parent'], $updatePim);
                }
            }
        }


        return Command::SUCCESS;
    }



    protected function getAllSaleChannels(){
       
    }


    protected function getAttributeSimpleScopable($productPim, $nameAttribute, $scope=null, $locale=null)
    {
        if (array_key_exists($nameAttribute, $productPim['values'])) {
            if ($locale) {
                foreach ($productPim['values'][$nameAttribute] as $attribute) {
                   
                    if ($attribute['locale']==$locale && $attribute['scope']==$scope) {
                        return $attribute['data'];
                    }
                }
            } else {
                foreach ($productPim['values'][$nameAttribute] as $attribute) {
                   
                    if ($attribute['scope']==$scope) {
                        return $attribute['data'];
                    }
                }
            }
        }
        return null;
    }
}
