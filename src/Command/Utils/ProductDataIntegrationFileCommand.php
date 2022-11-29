<?php

namespace App\Command\Utils;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\BusinessCentral\Connector\KpFranceConnector;
use App\Entity\Brand;
use App\Entity\Product;
use App\Entity\ProductSaleChannel;
use App\Entity\SaleChannel;
use App\Helper\Utils\CsvExtracter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProductDataIntegrationFileCommand extends Command
{
    protected static $defaultName = 'app:product-integration-from-file';
    protected static $defaultDescription = 'Import all products and brands from files';

    public function __construct(
        ManagerRegistry $manager,
        KpFranceConnector $kpFranceConnector,
        CsvExtracter $csvExtracter
    )
    {
        $this->manager = $manager->getManager();
        $this->kpFranceConnector = $kpFranceConnector;
        $this->csvExtracter = $csvExtracter;
        parent::__construct();
    }

    private $kpFranceConnector;

    private $manager;

    private $csvExtracter;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('pathFile', InputArgument::REQUIRED, 'Path of the file for injecting correlation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = $this->csvExtracter->extractAssociativeDatasFromCsv($input->getArgument('pathFile'));
        
        
        $saleChannels = $this->manager->getRepository(SaleChannel::class)->findAll();

        foreach ($products as $product) {
            $sku = $product['sku'];

            $output->writeln('Check Sku '.$sku);
            $productDb = $this->manager->getRepository(Product::class)->findOneBy([
                'sku' => $sku
            ]);


            if (!$productDb) {
                $output->writeln('Do no exists in Patxira '.$sku);
                $itemBc = $this->getBusinessCentralProduct($sku);
                if ($itemBc) {
                    $output->writeln('Exists in BC '.$sku);
                    $productDb = new Product();
                    $productDb->setSku($sku);
                    $productDb->setDescription($itemBc["displayName"]);
                    $productDb->setUnitCost($itemBc['unitCost']);
                    $this->manager->persist($productDb);
                    $this->manager->flush();

                    foreach ($saleChannels as $saleChannel) {
                        $productSaleChannel = new ProductSaleChannel();
                        $productSaleChannel->setProduct($productDb);
                        $saleChannel->addProductSaleChannel($productSaleChannel);
                    }

                   
                    if (array_key_exists("brand", $product)) {
                        $brand = $this->getBrand($product['brand']);
                        if ($brand) {
                            $brand->addProduct($productDb);
                        }
                    }
                    $output->writeln('Product creation >> ' . $sku);
                    $this->manager->flush();
                } else {
                    $output->writeln('Do no exists in Business central '.$sku);
                }
            }
        }
        return Command::SUCCESS;
    }



    private function getBusinessCentralProduct($sku)
    {
        return $this->kpFranceConnector->getItemByNumber($sku);
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
