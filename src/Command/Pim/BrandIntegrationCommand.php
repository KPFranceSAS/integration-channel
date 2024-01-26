<?php

namespace App\Command\Pim;

use App\Entity\Brand;
use App\Entity\Product;
use App\Service\Pim\AkeneoConnector;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:pim-product-brand-integration-from-pim', 'Import all brands')]
class BrandIntegrationCommand extends Command
{
    public function __construct(ManagerRegistry $manager, private readonly AkeneoConnector $akeneoConnector)
    {
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    private $manager;




    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = $this->akeneoConnector->getAllProducts();
        foreach ($products as $product) {
            /** @var \App\Entity\Product */
            $productDb = $this->manager->getRepository(Product::class)->findOneBy([
                'sku' => $product['identifier']
            ]);
            if ($productDb) {
                if (array_key_exists("brand", $product['values'])) {
                    $brand = $this->getBrand($product['values']['brand'][0]['data']);
                    if ($brand) {
                        $brand->addProduct($productDb);
                    }
                }
                $output->writeln('Product ' . $product['identifier']);
            } else {
                $output->writeln('<error>Product not found ' . $product['identifier'] . '</error>');
            }

            $this->manager->flush();
        }
        return Command::SUCCESS;
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
