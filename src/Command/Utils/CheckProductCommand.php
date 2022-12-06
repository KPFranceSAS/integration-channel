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
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CheckProductCommand extends Command
{
    protected static $defaultName = 'app:check-product';
    protected static $defaultDescription = 'Check all products';

    public function __construct(ManagerRegistry $manager, ValidatorInterface $validator)
    {
        $this->manager = $manager->getManager();
        $this->validator = $validator;
        parent::__construct();
    }

    private $manager;

    private $validator;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = $this->manager->getRepository(Product::class)->findAll();
        foreach ($products as $product) {
            $errors = $this->validator->validate($product);
            if (count($errors) > 0) {
                $output->writeln('SKU '.$product->getSku().'  >>> ' .(string) $errors);
            }
        }
      
        return Command::SUCCESS;
    }
}
