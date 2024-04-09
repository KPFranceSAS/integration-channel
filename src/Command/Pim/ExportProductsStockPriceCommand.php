<?php

namespace App\Command\Pim;

use App\BusinessCentral\ProductStockFinder;
use App\Entity\IntegrationChannel;
use App\Entity\Product;
use App\Entity\SaleChannel;
use App\Entity\WebOrder;
use Doctrine\Persistence\ManagerRegistry;
use League\Csv\Writer;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:export-product-stock-prices', 'Export product from Stock and prices')]
class ExportProductsStockPriceCommand extends Command
{
    public function __construct(private readonly LoggerInterface $logger, ManagerRegistry $managerRegistry, private readonly FilesystemOperator $productStorage, private readonly ProductStockFinder $productStockFinder)
    {
        $this->manager = $managerRegistry->getManager();
        
        parent::__construct();
    }

    private $manager;


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $integrationChannel = $this->manager->getRepository(IntegrationChannel::class)->findBy([
                'code' => IntegrationChannel::CHANNEL_CHANNELADVISOR
            ]);

            $saleChannels = $this->manager->getRepository(SaleChannel::class)->findBy([
                'integrationChannel' => $integrationChannel
            ]);
            /**
             * @var Product[] $products
             */
            $products = $this->manager->getRepository(Product::class)->findAll();
            $csv = Writer::createFromString();
            $csv->setDelimiter(';');
            
            $this->logger->info("start export ".count($products)." products");
            $header = ['sku', 'stock-laroca', 'stock-3pluk'];
            foreach ($saleChannels as $saleChannel) {
                $code = $saleChannel->getCode().'-';
                array_push($header, $code.'enabled', $code.'price', $code.'promoprice');
            }
            $csv->insertOne($header);
            $this->logger->info("start export ".count($products)." products on ".count($saleChannels)." sale channels");
            foreach ($products as $product) {
                $productArray = $this->addProduct($product, $header, $saleChannels);
                $csv->insertOne(array_values($productArray));
            }
            $this->logger->info("start export prices and stock locally");
            $this->productStorage->write('export_prices_stocks_sftp.csv', $csv->toString());
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
        return Command::SUCCESS;
    }


    private function addProduct(Product $product, array $header, array $saleChannels): array
    {
        $productArray = array_fill_keys($header, null);
        $productArray['sku'] = $product->getSku();
        $productArray['stock-laroca'] = $this->productStockFinder->getRealStockProductWarehouse($product->getSku(), WebOrder::DEPOT_LAROCA);
        $productArray['stock-3pluk'] = $this->productStockFinder->getRealStockProductWarehouse($product->getSku(), WebOrder::DEPOT_3PLUK);


        foreach ($saleChannels as $saleChannel) {
            $code = $saleChannel->getCode().'-';
            $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());
          
            if ($productMarketplace->getEnabled()) {
                $productArray[$code.'enabled']= 1 ;
                $productArray[$code.'price']= $productMarketplace->getPrice() ;
                $promotion = $productMarketplace->getBestPromotionForNow();
                if ($promotion) {
                    $productArray[$code.'promoprice']= $promotion->getPromotionPrice() ;
                }
            } else {
                $productArray[$code.'enabled']= 0;
            }
        }

        return $productArray;
    }
}
