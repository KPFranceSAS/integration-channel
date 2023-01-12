<?php

namespace App\Service\Amazon\Report;

use App\Entity\AmazonFinancialEvent;
use App\Entity\AmazonFinancialEventGroup;
use App\Entity\AmazonReimbursement;
use App\Entity\AmazonReturn;
use App\Entity\FbaReturn;
use App\Entity\Product;
use App\Entity\ProductStockDaily;
use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class PublishPowerBi
{
    const BATCH_SIZE = 1000;
    public function __construct(LoggerInterface $logger, ManagerRegistry $manager, SerializerInterface $serializer, $projectDir)
    {
        /** @var \Doctrine\ORM\EntityManagerInterface */
        $this->manager = $manager->getManager();
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->projectDir =  $projectDir.'/public/report/';
    }

    private $manager;

    private $logger;

    private $projectDir;

    private $serializer;


    public function exportAll()
    {
        $this->exportMarketplaceNames();
        $this->exportProducts();
        $this->exportProductsStock();
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
        $query = "select a from ".$className." a";
        $this->exportDataQuery($query, $groupSerialisation, $fileName);
        $this->logger->info("Export " . $className . " done ");
    }




    

    public function exportProducts()
    {
        $this->exportData(Product::class, 'export_product', 'products.json');
    }

    public function exportProductsStock()
    {
        $this->exportData(ProductStockDaily::class, 'export_product', 'productstocks.json');
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


    public function exportMarketplaceNames()
    {
        $results = $this->manager->createQuery('select DISTINCT a.salesChannel from App\Entity\AmazonOrder a')->getArrayResult();
        $marketplaces = [];
        foreach ($results as $result) {
            $marketplaces[] = [
                'name' => $result['salesChannel']
            ];
        }
        $filePath = $this->projectDir.'marketplaces.json';

        $filesystem = new Filesystem();
        $filesystem->remove($filePath);
        $this->manager->clear();
        $filesystem->appendToFile($filePath, json_encode($marketplaces));
    }






    public function exportOrders()
    {
        $this->logger->info("Export orders start ");
        $query = 'select a from App\Entity\AmazonOrder a where a.isReturn = 0';
        $this->exportDataQuery($query, 'export_order', 'orders.json');
        $this->logger->info("Export orders done ");
    }


    public function exportFinancials()
    {
        $this->exportData(AmazonFinancialEvent::class, 'export_order', 'financials.json');
    }


    public function exportDataQuery($query, $groupSerialisation, $fileName)
    {
        $filePath = $this->projectDir.$fileName;

        $filesystem = new Filesystem();
        $filesystem->remove($filePath);

        $this->logger->info("Export datas : ".$query);
        $datas = [];
        $filesystem->appendToFile($filePath, "[");
        $i = 1;
        $firstAdded = false;
        $q = $this->manager->createQuery($query);
        foreach ($q->toIterable() as $amz) {
            $data = $this->serializer->serialize($amz, 'json', [
                DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s',
                'groups' => $groupSerialisation
            ]);
            $datas[] = json_decode($data);
            ++$i;
            if (($i % self::BATCH_SIZE) === 0) {
                $this->logger->info("Exported  $i datas in ".$fileName);
                $this->manager->clear(); // Detaches all objects from Doctrine!
                $valueToadd = substr(json_encode($datas), 1, -1);

                if ($firstAdded) {
                    $valueToadd =  ','. $valueToadd;
                } else {
                    $firstAdded = true;
                }
                $filesystem->appendToFile($filePath, $valueToadd);
                $datas = [];
            }
        }
        if (count($datas)>0) {
            $valueToadd = substr(json_encode($datas), 1, -1);
            if ($firstAdded) {
                $valueToadd =  ','. $valueToadd;
            } else {
                $firstAdded = true;
            }
            $filesystem->appendToFile($filePath, $valueToadd);
        }

        $filesystem->appendToFile($filePath, ']');
        $this->logger->info("Export datas done ");
    }
}
