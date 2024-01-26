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

#[\Symfony\Component\Console\Attribute\AsCommand('app:check-product', 'Check all products')]
class CheckProductCommand extends Command
{
    public function __construct(ManagerRegistry $manager, private readonly ValidatorInterface $validator)
    {
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    private $manager;

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
