<?php

namespace App\Service\Amazon\Report;

use App\Entity\AmazonFinancialEventGroup;
use App\Entity\AmazonReimbursement;
use App\Entity\AmazonReturn;
use App\Entity\FbaReturn;
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
        /** @var \Doctrine\ORM\EntityManagerInterface */
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
        $this->exportFbas();
        $this->exportReimbursements();
        $this->exportReturns();
        $this->exportFinancialGroups();
        $this->exportFinancials();
        $this->exportOrders();
    }


    public function exportData($className, $groupSerialisation, $fileName)
    {
        $this->logger->info("Export " . $className);
        $elements  = $this->manager->getRepository($className)->findAll();
        $this->kpssportStorage->write($fileName, $this->serializer->serialize($elements, 'json', [
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


    public function exportFbas()
    {
        $this->exportData(FbaReturn::class, 'export_order', 'fbareturns.json');
    }

    public function exportReimbursements()
    {
        $this->exportData(AmazonReimbursement::class, 'export_order', 'reimbursements.json');
    }


    public function exportFinancialGroups()
    {
        $this->exportData(AmazonFinancialEventGroup::class, 'export_order', 'financial_groups.json');
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

        $this->kpssportStorage->write('orders.json', json_encode($orders), []);
        $this->logger->info("Export orders done ");
    }


    public function exportFinancials()
    {
        $this->logger->info("Export financials ");
        $financials = [];
        $batchSize = 200;
        $i = 1;
        $q = $this->manager->createQuery('select a from App\Entity\AmazonFinancialEvent a');
        foreach ($q->toIterable() as $amz) {
            $financial = $this->serializer->serialize($amz, 'json', [
                DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s',
                'groups' => 'export_order'
            ]);
            $financials[] = json_decode($financial);
            ++$i;
            if (($i % $batchSize) === 0) {
                $this->logger->info("Exported  $i financials ");
                $this->manager->clear(); // Detaches all objects from Doctrine!
            }
        }

        $this->kpssportStorage->write('financials.json', json_encode($financials), []);
        $this->logger->info("Export financials done ");
    }
}
