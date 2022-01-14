<?php

namespace App\Command\Pim;

use App\Entity\Brand;
use App\Entity\Product;
use App\Service\Pim\AkeneoConnector;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BrandIntegrationCommand extends Command
{
    protected static $defaultName = 'app:pim-product-brand-integration-from-pim';
    protected static $defaultDescription = 'Import all products and brands';

    public function __construct(ManagerRegistry $manager, AkeneoConnector $akeneoConnector)
    {
        $this->manager = $manager->getManager();
        $this->akeneoConnector = $akeneoConnector;
        parent::__construct();
    }

    private $manager;

    private $akeneoConnector;




    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = $this->akeneoConnector->getAllProducts();
        foreach ($products as $product) {
            $productDb = $this->manager->getRepository(Product::class)->findOneBy([
                'sku' => $product['identifier']
            ]);
            if (!$productDb) {
                $productDb = new Product();
                $productDb->setSku($product['identifier']);
                $this->manager->persist($productDb);
            }
            if (array_key_exists("asin", $product['values'])) {
                $productDb->setAsin($product['values']['asin'][0]['data']);
            }

            if (array_key_exists("brand", $product['values'])) {
                $brand = $this->getBrand($product['values']['brand'][0]['data']);
                if ($brand) {
                    $brand->addProduct($productDb);
                }
            }


            if (array_key_exists("erp_name", $product['values'])) {
                $productDb->setDescription($product['values']['erp_name'][0]['data']);
            }
            $output->writeln('Product ' . $product['identifier']);
            $this->manager->flush();
        }
        return Command::SUCCESS;
    }



    private function getBrand(string $brandName): Brand
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
