<?php

namespace App\Command\Amazon\Import;

use App\Entity\Product;
use App\Entity\ProductCorrelation;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:amz-add-missing-products', 'Add missing products')]
class AddMissingProductsCommand extends Command
{
    protected $manager;

    protected $logger;

    public function __construct(LoggerInterface $logger, ManagerRegistry $manager)
    {
        $this->logger = $logger;
        /** @var \Doctrine\ORM\EntityManagerInterface */
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info("Export orders ");

        $batchSize = 200;
        $i = 1;
        $q = $this->manager->createQuery('select a from App\Entity\AmazonOrder a where a.product IS NULL');
        foreach ($q->toIterable() as $amz) {
            $this->addProductByAsin($amz);
            ++$i;
            if (($i % $batchSize) === 0) {
                $this->logger->info("Saved  $i orders ");
                $this->manager->flush();
                $this->manager->clear();
            }
        }
        $this->manager->flush();
        $this->manager->clear();


        $i = 1;
        $q = $this->manager->createQuery('select a from App\Entity\AmazonReturn a where a.product IS NULL');
        foreach ($q->toIterable() as $amz) {
            $this->addProductByFnskuSku($amz);
            ++$i;
            if (($i % $batchSize) === 0) {
                $this->logger->info("Saved  $i returns ");
                $this->manager->flush();
                $this->manager->clear();
            }
        }
        $this->manager->flush();
        $this->manager->clear();


        $i = 1;
        $q = $this->manager->createQuery('select a from App\Entity\AmazonReimbursement a where a.product IS NULL');
        foreach ($q->toIterable() as $amz) {
            $this->addProductByFnskuSku($amz);
            ++$i;
            if (($i % $batchSize) === 0) {
                $this->logger->info("Saved  $i Reimbursement ");
                $this->manager->flush();
                $this->manager->clear();
            }
        }
        $this->manager->flush();
        $this->manager->clear();


        $this->logger->info("Export orders done ");




        return Command::SUCCESS;
    }



    protected function addProductByFnskuSku($amz)
    {
        $product = $this->manager->getRepository(Product::class)->findOneBy([
            'fnsku' => $amz->getFnsku(),
            "sku" => $this->getProductCorrelationSku($amz->getSku())
        ]);
        if ($product) {
            $amz->setProduct($product);
        } else {
            $product = $this->manager->getRepository(Product::class)->findOneBy([
                "sku" => $this->getProductCorrelationSku($amz->getSku())
            ]);
            if ($product) {
                $amz->setProduct($product);
            }
        }
    }



    protected function addProductByAsin($amz)
    {
        $product = $this->manager->getRepository(Product::class)->findOneBy([
            'asin' => $amz->getAsin(),
            "sku" => $this->getProductCorrelationSku($amz->getSku())
        ]);
        if ($product) {
            $amz->setProduct($product);
        } else {
            $product = $this->manager->getRepository(Product::class)->findOneBy([
                "sku" => $this->getProductCorrelationSku($amz->getSku())
            ]);
            if ($product) {
                $amz->setProduct($product);
            }
        }
    }


    /**
     * Undocumented function
     *
     * @return string
     */
    protected function getProductCorrelationSku(string $sku): string
    {
        $skuSanitized = strtoupper($sku);
        $productCorrelation = $this->manager->getRepository(ProductCorrelation::class)->findOneBy(['skuUsed' => $skuSanitized]);
        return $productCorrelation ? $productCorrelation->getSkuErpBc() : $skuSanitized;
    }
}
