<?php

namespace App\Command\Utils;

use App\Entity\Product;
use App\Entity\ProductCorrelation;
use App\Helper\Utils\CsvExtracter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AffectCorrelationCommand extends Command
{
    protected static $defaultName = 'app:affect-correlation';
    protected static $defaultDescription = 'Affect all Correlations to a product';

    public function __construct(ManagerRegistry $manager)
    {
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    private $manager;



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $correlations = $this->manager->getRepository(ProductCorrelation::class)->findAll();
        foreach ($correlations as $correlation) {
           
            if (!$correlation->getProduct()) {
                $product = $this->manager->getRepository(Product::class)->findOneBy([
                    'sku'=>$correlation->getSkuErp()
                ]);
                if ($product) {
                    $correlation->setProduct($product);
                } else {
                    $this->output->writeln('No product for SKU '.$correlation->getSkuErp());
                }
               
            } 
        }
        $this->manager->flush();
      
        return Command::SUCCESS;
    }
}
