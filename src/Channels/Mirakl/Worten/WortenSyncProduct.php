<?php

namespace App\Channels\Mirakl\Worten;

use App\Channels\Mirakl\MiraklSyncProductParent;
use App\Entity\IntegrationChannel;
use League\Csv\Writer;
use Symfony\Component\Filesystem\Filesystem;

class WortenSyncProduct extends MiraklSyncProductParent
{


    public function sendProducts(array $products, $header)
    {
        $csv = Writer::createFromString();
        $csv->setDelimiter(';');
        $csv->insertOne($header);
        $this->logger->info("start export ".count($products)." products");
        foreach ($products as $product) {
            $productArray = $this->addProduct($product, $header);
            $csv->insertOne(array_values($productArray));
        }
        $csvContent = $csv->toString();
        $filename = $this->projectDir.'export_products_'.$this->getLowerChannel().'_'.date('Ymd_His').'.csv';
        $finalFile = $this->projectDir.'export_products_'.$this->getLowerChannel().'.csv';
        $this->logger->info("start export products locally");

        $fs = new Filesystem();
        $fs->appendToFile($filename, $csvContent);
        $fs->remove($finalFile);
        $fs->appendToFile($finalFile, $csvContent);

        $this->logger->info("start export products on Mirakl");
        //$this->getMiraklApi()->sendProductImports($filename);
    }



    protected function getMarketplaceNode(): string
    {
        return 'worten';
    }



    public function getLocales(): array
    {
        return [
            'es_ES', 'pt_PT', 'en_GB'
        ];
    }




    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_WORTEN;
    }
}
