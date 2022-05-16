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
                $categoryDb = $this->manager->getRepository(Category::class)->findOneBy(["name" => $product['Category']]);
                $productDb->setDescription($product['ERP Name']);
                if ($categoryDb) {
                    $productDb->setCategory($categoryDb);
                } else {
                    $output->writeln('category not found ' . $product['Category']);
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


    protected function getProductCorrelation($sku)
    {
        $productDb = $this->manager->getRepository(Product::class)->findOneBy(["sku" => $sku]);
        if (!$productDb) {
            $productDb = $this->manager->getRepository(ProductCorrelation::class)->findOneBy(["skuM" => $sku]);
        }
    }



    protected function getProductCorrelationSku(string $sku)
    {
        $skuSanitized = strtoupper($sku);
        $productCorrelation = $this->manager->getRepository(ProductCorrelation::class)->findOneBy(['skuUsed' => $skuSanitized]);
        $skuFinal = $productCorrelation ? $productCorrelation->getSkuErp() : $skuSanitized;

        return  $this->manager->getRepository(Product::class)->findOneBy(["sku" => $skuFinal]);
    }
}
