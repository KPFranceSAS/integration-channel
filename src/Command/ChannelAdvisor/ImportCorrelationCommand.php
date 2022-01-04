<?php

namespace App\Command\ChannelAdvisor;

use App\Entity\ProductCorrelation;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCorrelationCommand extends Command
{
    protected static $defaultName = 'app:import-correlation';
    protected static $defaultDescription = 'Import all Correlations';

    public function __construct(ManagerRegistry $manager)
    {
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    private $manager;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $products = $this->initializeDatasFromCsv();

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
        return Command::SUCCESS;
    }

    public function initializeDatasFromCsv(): array
    {
        $contentFile = fopen(__DIR__ . '/../../../docs/equivalence.csv', "r");
        $products = [];
        $header = fgetcsv($contentFile, null, ';');
        while (($values = fgetcsv($contentFile, null, ';')) !== false) {
            if (count($values) == count($header)) {
                $dataProducts = array_combine($header, $values);
                $products[] = $dataProducts;
            }
        }
        return $products;
    }
}
