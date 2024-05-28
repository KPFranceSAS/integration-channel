<?php

namespace App\Command\Pim;

use App\Entity\Product;
use App\Service\Pim\AkeneoConnector;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:sync-product-type-from-pim', 'Sync product type with product type')]
class SyncProductTypeIntegration extends Command
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
                $productType = array_key_exists("mkp_product_type", $product['values']) ? $product['values']['mkp_product_type'][0]['data'] : null;

                if($productType!=$productDb->getProductType()){
                    $output->writeln('Product ' . $product['identifier']. " updated to ".$productType);
                    $productDb->setProductType($productType);
                }
                
            } else {
                $output->writeln('<error>Product not found ' . $product['identifier'] . '</error>');
            }

          
        }
        $this->manager->flush();
        return Command::SUCCESS;
    }


}
