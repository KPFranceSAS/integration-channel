<?php

namespace App\Command\Utils;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductCorrelation;
use App\Helper\Utils\CsvExtracter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportProductCategoryCommand extends Command
{
    protected static $defaultName = 'app:import-product-category';
    protected static $defaultDescription = 'Import all products / categoreis';

    public function __construct(ManagerRegistry $manager, CsvExtracter $csvExtracter)
    {
        $this->manager = $manager->getManager();
        $this->csvExtracter = $csvExtracter;
        parent::__construct();
    }

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

        $output->writeln('Start imports ' . count($products));
        foreach ($products as $product) {
            $productDb = $this->getProductCorrelationSku($product["Sku"]);
            if ($productDb) {

                if (array_key_exists('Category', $product)) {
                    $categoryDb = $this->manager->getRepository(Category::class)->findOneBy(["name" => $product['Category']]);
                    $productDb->setDescription($product['ERP Name']);
                    if ($categoryDb) {
                        $productDb->setCategory($categoryDb);
                    } else {
                        $output->writeln('category not found ' . $product['Category']);
                    }
                }

                if (array_key_exists('Min Stock EU', $product)) {
                    $value = (int)$product['Min Stock EU'];
                    if ($value > 0) {
                        $productDb->setMinQtyFbaEu($value);
                    } else {
                        $productDb->setMinQtyFbaEu(null);
                    }
                }


                if (array_key_exists('Min Stock UK', $product)) {
                    $value = (int)$product['Min Stock UK'];
                    if ($value > 0) {
                        $productDb->setMinQtyFbaUk($value);
                    } else {
                        $productDb->setMinQtyFbaUk(null);
                    }
                }


                $this->manager->persist($productDb);
                $this->manager->flush();
            } else {
                $output->writeln('Product not found ' . $product['Sku']);
            }
        }
        $output->writeln('Finish imports ' . count($products));
        return Command::SUCCESS;
    }



    protected function getProductCorrelationSku(string $sku)
    {
        $skuSanitized = strtoupper($sku);
        $productCorrelation = $this->manager->getRepository(ProductCorrelation::class)->findOneBy(['skuUsed' => $skuSanitized]);
        $skuFinal = $productCorrelation ? $productCorrelation->getSkuErp() : $skuSanitized;

        return  $this->manager->getRepository(Product::class)->findOneBy(["sku" => $skuFinal]);
    }
}
