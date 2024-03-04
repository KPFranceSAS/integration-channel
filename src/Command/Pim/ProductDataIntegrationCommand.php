<?php

namespace App\Command\Pim;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\BusinessCentral\LogisticClassFinder;
use App\Entity\Brand;
use App\Entity\Product;
use App\Entity\ProductSaleChannel;
use App\Entity\SaleChannel;
use App\Helper\MailService;
use App\Service\Pim\AkeneoConnector;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:pim-product-integration-from-pim', 'Import all products and brands')]
class ProductDataIntegrationCommand extends Command
{
    public function __construct(
        ManagerRegistry $manager,
        private readonly AkeneoConnector $akeneoConnector,
        private readonly KitPersonalizacionSportConnector $kitPerzonalizacionConnector,
        private readonly LogisticClassFinder $logisticClassFinder,
        private readonly MailService $mailService
    ) {
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    private $manager;




    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Start retrieve datas from Akeneo');

        $searchBuilder = new SearchBuilder();
        $searchBuilder
             ->addFilter('erp_product_type', 'IN', ['goods']);


        $products = $this->akeneoConnector->searchProducts($searchBuilder, 'Marketplace');
        $errors = [];
        $messages = [];

        $saleChannels = $this->manager->getRepository(SaleChannel::class)->findAll();

        $i=1;
        foreach ($products as $product) {
            $sku = $product['identifier'];
            $output->writeln('Check Sku '.$sku);
            $productDb = $this->manager->getRepository(Product::class)->findOneBy([
                'sku' => $sku
            ]);

           

            if (!$productDb) {
                $output->writeln('Do no exists in Patxira '.$sku);
                $itemBc = $this->getBusinessCentralProduct($sku);
                if ($itemBc) {
                    $productDb = new Product();
                    $productDb->setSku($sku);
                    $productDb->setDescription($itemBc["displayName"]);
                    if (array_key_exists("ean", $product['values'])) {
                        $ean = $product['values']['ean'][0]['data'];
                        if($ean!=$productDb->getEan()) {
                            $productDb->setEan($ean);
                        }
                    }

                    

                    $this->manager->persist($productDb);
                    $this->manager->flush();

                    foreach ($saleChannels as $saleChannel) {
                        $productSaleChannel = new ProductSaleChannel();
                        $productSaleChannel->setProduct($productDb);
                        $saleChannel->addProductSaleChannel($productSaleChannel);
                    }
                    $this->manager->flush();

                   
                    if (array_key_exists("brand", $product['values'])) {
                        $brand = $this->getBrand($product['values']['brand'][0]['data']);
                        if ($brand) {
                            $brand->addProduct($productDb);
                        }
                    }
                    $this->manager->flush();
                    $output->writeln('Product creation >> ' . $sku);
                 
                    $messages[] = "Product with SKU ".$sku." has been added to Patxira. You need to enable it on Marketplace.";
                } else {
                    $output->writeln('Do no exists in Business central '.$sku);
                    $errors[] = "Product with SKU ".$sku." exists in PIM but not in Business central. Please correct product in PIM to Business central SKU.";
                }
            } else {
                if (array_key_exists("ean", $product['values'])) {
                    $ean = $product['values']['ean'][0]['data'];
                    if($ean!=$productDb->getEan()) {
                        $productDb->setEan($ean);
                    }
                }

                if (array_key_exists("brand", $product['values'])) {
                    $brand = $this->getBrand($product['values']['brand'][0]['data']);
                    if ($brand) {
                        $brand->addProduct($productDb);
                    }
                }

                $productDb->setActive($product['enabled']);

            }

            if($i%50 == 0) {
                $this->manager->flush();
                $this->manager->clear();
                $saleChannels = $this->manager->getRepository(SaleChannel::class)->findAll();
            }
            $i++;
        }

        $this->manager->flush();


        if (count($errors)>0) {
            $this->mailService->sendEmailRole('ROLE_PRICING', '[Products] Error PIM with products', implode('<br/>', $errors));
        }


        if (count($messages)>0) {
            $this->mailService->sendEmailRole('ROLE_PRICING', '[Pricing] New products to configure', implode('<br/>', $messages));
        }
        return Command::SUCCESS;
    }



    private function getBusinessCentralProduct($sku)
    {
        $item = $this->kitPerzonalizacionConnector->getItemByNumber($sku);
        return $item;
    }



    private function getBrand(string $brandName): ?Brand
    {
        $nameSanitized =  strtoupper($brandName);
        if (strlen($nameSanitized) == 0) {
            return null;
        }
        $brand = $this->manager->getRepository(Brand::class)->findOneBy(['name' => $nameSanitized]);
        if (!$brand) {
            $brand = new Brand();
            $brand->setName($nameSanitized);
            $this->manager->persist($brand);
        }
        return $brand;
    }
}
