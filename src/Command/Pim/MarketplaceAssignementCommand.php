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

#[\Symfony\Component\Console\Attribute\AsCommand('app:sync-marketplace-assignments', 'Add marketplace assignments')]
class MarketplaceAssignementCommand extends Command
{
    public function __construct(
        ManagerRegistry $manager,
        private readonly AkeneoConnector $akeneoConnector
    ) {
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    private $manager;




    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $saleChannels = $this->getAllSaleChannels();
        $products= $this->akeneoConnector->getAllProducts();
        foreach ($products as $product) {
            $output->writeln('-----------------------------------');

            $output->writeln('Check Product '.$product['identifier']);
            $productDb = $this->manager->getRepository(Product::class)->findOneBySku($product['identifier']);
            if($productDb) {
                $productAssignation = $this->getAttributeSimpleScopable($product, 'marketplaces_assignement');
                if(!$productAssignation) {
                    $productAssignation=[];
                }
                sort($productAssignation);
                $productAssignationBak = json_encode($productAssignation);
                $output->writeln('Product assignation > '.$productAssignationBak);

                foreach($productDb->getProductSaleChannels() as $productSaleChannel) {
                    $output->writeln('Check '.$productSaleChannel);
                    if(in_array($productSaleChannel->getSaleChannel()->getCode(), $saleChannels)) {
                        
                        $codePim = $productSaleChannel->getSaleChannel()->getCodePim();
                       
                        $key = array_search($codePim, $productAssignation);
                        $output->writeln('Check '.$codePim ." >> ". $key ? 'true' : 'false');
                        if($productSaleChannel->getEnabled()) {
                            if ($key == false) {
                                $output->writeln('Add '.$codePim);
                                $productAssignation[]=$codePim;
                            }
                        } else {
                            if ($key !== false) {
                                $output->writeln('Remove '.$codePim);
                                unset($productAssignation[$key]);
                            }
                        }
                    } else {
                        $output->writeln('Not present '.$productSaleChannel->getSaleChannel()->getCode());
                    }
                }

                $productAssignation =array_unique($productAssignation);
                sort($productAssignation);
                $output->writeln('Product nv assignation > '.json_encode($productAssignation));

                
                $updatePim = [];

                if($productAssignationBak!=json_encode($productAssignation)) {
                    $output->writeln('Move from '.$productAssignationBak.' to '.json_encode($productAssignation));
                    $updatePim['marketplaces_assignement']=[
                        [
                            'data' => $productAssignation,
                            'scope'=>  null,
                            'locale' => null
                        ]
                    ];

                }

                $productEnabledOnMarketPlacePim = $this->getAttributeSimpleScopable($product, 'enabled_channel', 'Marketplace');
                $enabledOnMArketplace = count($productAssignation) > 0;

                if($productEnabledOnMarketPlacePim!==$enabledOnMArketplace) {
                    $updatePim['enabled_channel']=[
                        [
                            'data' => $enabledOnMArketplace,
                            'scope'=>  'Marketplace',
                            'locale' => null
                        ]
                        ];
                        $output->writeln('Change to update '.$enabledOnMArketplace ? 'true' : 'false');
                }

                if(count($updatePim)>0) {
                    $output->writeln('Update pim '.$product['identifier'].' >>> '. $product['parent']);
                    $this->akeneoConnector->updateProductParent($product['identifier'], $product['parent'], $updatePim);
                }
            }




           
        }
        return Command::SUCCESS;
    }



    protected function getAllSaleChannels()
    {
        $saleChannels = [];
        $integrationChannels = $this->manager->getRepository(IntegrationChannel::class)->findBy(['active'=>true]);
        foreach($integrationChannels as $integrationChannel) {
            foreach($integrationChannel->getSaleChannels() as $saleChannel) {
                if($saleChannel->getCodePim()) {
                    $saleChannels[$saleChannel->getCodePim()] = $saleChannel->getCode();
                }
            }
        }
        return $saleChannels;
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
