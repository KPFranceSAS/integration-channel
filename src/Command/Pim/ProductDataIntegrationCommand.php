<?php

namespace App\Command\Pim;

use App\Entity\Brand;
use App\Entity\Product;
use App\Entity\ProductSaleChannel;
use App\Entity\SaleChannel;
use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\Service\MailService;
use App\Service\Pim\AkeneoConnector;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProductDataIntegrationCommand extends Command
{
    protected static $defaultName = 'app:pim-product-integration-from-pim';
    protected static $defaultDescription = 'Import all products and brands';

    public function __construct(
        ManagerRegistry $manager,
        AkeneoConnector $akeneoConnector,
        KitPersonalizacionSportConnector $kitPerzonalizacionConnector,
        MailService $mailService
    ) {
        $this->manager = $manager->getManager();
        $this->akeneoConnector = $akeneoConnector;
        $this->kitPerzonalizacionConnector = $kitPerzonalizacionConnector;
        $this->mailService = $mailService;
        parent::__construct();
    }

    private $kitPerzonalizacionConnector;

    private $mailService;

    private $manager;

    private $akeneoConnector;




    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Start retrieve datas from Akeneo');
        $products = $this->akeneoConnector->getAllProducts();
        $errors = [];
        $messages = [];

        $saleChannels = $this->manager->getRepository(SaleChannel::class)->findAll();

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
                    $this->manager->persist($productDb);
                    $this->manager->flush();

                    foreach ($saleChannels as $saleChannel) {
                        $productSaleChannel = new ProductSaleChannel();
                        $productSaleChannel->setProduct($productDb);
                        $saleChannel->addProductSaleChannel($productSaleChannel);
                    }

                   
                    if (array_key_exists("brand", $product['values'])) {
                        $brand = $this->getBrand($product['values']['brand'][0]['data']);
                        if ($brand) {
                            $brand->addProduct($productDb);
                        }
                    }
                    $output->writeln('Product creation >> ' . $sku);
                    $this->manager->flush();
                    $messages[] = "Product with SKU ".$sku." has been added to Patxira. You need to enable it on Marketplace.";
                } else {
                    $output->writeln('Do no exists in Business central '.$sku);
                    $errors[] = "Product with SKU ".$sku." exists in PIM but not in Business central. Please correct product in PIM to Business central SKU.";
                }
            }
        }


        if(count($errors)>0){
            $this->mailService->sendEmail('[Products] Error PIM with products', implode('<br/>', $errors), 'devops@kpsport.com');
        }


        if(count($messages)>0){
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
