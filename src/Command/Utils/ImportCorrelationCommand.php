<?php

namespace App\Command\Utils;

use App\Entity\ProductCorrelation;
use App\Helper\Utils\CsvExtracter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCorrelationCommand extends Command
{
    protected static $defaultName = 'app:import-correlation';
    protected static $defaultDescription = 'Import all Correlations';

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
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pathFile = __DIR__ . '/../../../docs/equivalence.csv';
        $products = $this->csvExtracter->extractAssociativeDatasFromCsv($pathFile);
        $output->writeln('Start imports ' . count($products));
        foreach ($products as $product) {
            $correlation = $this->manager->getRepository(ProductCorrelation::class)->findOneBy(["skuUsed" => $product['skuUsed']]);
            if (!$correlation) {
                $productCorrelation = new ProductCorrelation();
                $productCorrelation->setSkuUsed($product['skuUsed']);
                $productCorrelation->setSkuErp($product['skuErp']);
                $this->manager->persist($productCorrelation);
                $this->manager->flush();
            }
        }
        $output->writeln('Finish imports ' . count($products));
        return Command::SUCCESS;
    }
}
