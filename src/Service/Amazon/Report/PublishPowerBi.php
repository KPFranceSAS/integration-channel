<?php

namespace App\Service\Amazon\Report;

use App\Entity\AmazonReimbursement;
use App\Entity\AmazonReturn;
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
        $this->exportReimbursements();
        $this->exportReturns();
    }


    public function exportData($className, $groupSerialisation, $fileName)
    {
        $this->logger->info("Export " . $className);
        $elements  = $this->manager->getRepository($className)->findAll();
        $this->kpssportStorage->write($fileName,  $this->serializer->serialize($elements, 'json', [
            DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s',
            'groups' => $groupSerialisation
        ]), []);
        $this->logger->info("Export " . $className . " done ");
    }




    public function exportProducts()
    {
        $this->exportData(Product::class, 'export_product', 'products.json');
    }



    public function exportReturns()
    {
        $this->exportData(AmazonReturn::class, 'export_order', 'returns.json');
    }


    public function exportReimbursements()
    {
        $this->exportData(AmazonReimbursement::class, 'export_order', 'reimbursements.json');
    }




    public function exportOrders()
    {
        $this->logger->info("Export orders ");
        $orders = [];
        $batchSize = 200;
        $i = 1;
        $q = $this->manager->createQuery('select a from App\Entity\AmazonOrder a');
        foreach ($q->toIterable() as $amz) {
            if ($amz->getIsReturn() == false) {
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
        }

        $this->kpssportStorage->write('orders.json',  json_encode($orders), []);
        $this->logger->info("Export orders done ");
    }
}
