<?php

namespace App\Service\Amazon;

use App\Entity\Product;
use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;


class PublishPowerBi
{
    public function __construct(LoggerInterface $logger, ManagerRegistry $manager, SerializerInterface $serializer, FilesystemOperator $kpssportStorage)
    {
        $this->kpssportStorage = $kpssportStorage;
        $this->manager = $manager->getManager();
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    private $manager;

    private $logger;

    private $serializer;

    private $kpssportStorage;



    public function exportAll()
    {
        $this->exportProducts();
        $this->exportOrders();
    }


    public function exportProducts()
    {
        $this->logger->info("Export products ");
        $products  = $this->manager->getRepository(Product::class)->findAll();


        $this->kpssportStorage->write('products.json',  $this->serializer->serialize($products, 'json', [
            DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s',
            'groups' => 'export_product'
        ]), []);
        $this->logger->info("Export products done ");
    }




    public function exportOrders()
    {
        $this->logger->info("Export orders ");
        $orders = [];
        $batchSize = 200;
        $i = 1;
        $q = $this->manager->createQuery('select a from App\Entity\AmazonOrder a');
        foreach ($q->toIterable() as $amz) {


            $order = $this->serializer->serialize($amz, 'json', [
                DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s',
                'groups' => 'export_order'
            ]);
            $orders[] = json_decode($order);
            ++$i;
            if (($i % $batchSize) === 0) {
                $this->logger->info("Exported  $i orders ");
                $this->manager->clear(); // Detaches all objects from Doctrine!
            }
        }

        $this->kpssportStorage->write('orders.json',  json_encode($orders), []);
        $this->logger->info("Export orders done ");
    }
}
