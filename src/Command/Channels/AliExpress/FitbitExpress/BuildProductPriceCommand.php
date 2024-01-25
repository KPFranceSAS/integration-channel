<?php

namespace App\Command\Channels\AliExpress\FitbitExpress;

use App\BusinessCentral\Connector\GadgetIberiaConnector;
use App\Channels\AliExpress\FitbitExpress\FitbitExpressApi;
use App\Entity\Brand;
use App\Entity\Product;
use App\Entity\ProductSaleChannel;
use App\Entity\Promotion;
use App\Entity\SaleChannel;
use DateInterval;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildProductPriceCommand extends Command
{
    protected static $defaultName = 'app:fitbitexpress-build-product-prices';
    protected static $defaultDescription = 'Build product for fitbitexpress';

    public function __construct(private readonly GadgetIberiaConnector $bcConnector, ManagerRegistry $manager, private readonly FitbitExpressApi $aliExpressApi)
    {
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    private $manager;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = $this->aliExpressApi->getAllActiveProducts();

        $now = new DateTime();
        $now->sub(new DateInterval('P3D'));

        $end = new DateTime();
        $end->add(new DateInterval('P750D'));
      
        $saleChannels = $this->manager->getRepository(SaleChannel::class)->findAll();
        $progressPar = new ProgressBar($output, count($products));
        $progressPar->start();

        foreach ($products as $product) {
            $output->writeln('Check skus for ' . $product->subject . ' / Id ' . $product->product_id);
            $productInfo = $this->getProductInfo($product->product_id);
            if ($productInfo) {
                $brandName = $this->extractBrandFromResponse($productInfo);
                foreach ($productInfo->aeop_ae_product_s_k_us->global_aeop_ae_product_sku as $variant) {
                    $sku = $variant->sku_code;
                    $output->writeln('Check Sku '.$sku);
                    $productDb = $this->manager->getRepository(Product::class)->findOneBy([
                        'sku' => $sku
                    ]);

                    if (!$productDb) {
                        $output->writeln('Do no exists in Patxira '.$sku);
                        $itemBc = $this->getBusinessCentralProduct($sku);
                        if ($itemBc) {
                            $output->writeln('Exists in BC '.$sku);
                            $productDb = new Product();
                            $productDb->setSku($sku);
                            $productDb->setDescription($itemBc["displayName"]);
                            $productDb->setUnitCost($itemBc['unitCost']);
                            $this->manager->persist($productDb);
                            $this->manager->flush();
        
                            foreach ($saleChannels as $saleChannel) {
                                $productSaleChannel = new ProductSaleChannel();
                                $productDb->addProductSaleChannel($productSaleChannel);
                                $saleChannel->addProductSaleChannel($productSaleChannel);
                            }

                            $brand = $this->getBrand($brandName);
                            if ($brand) {
                                $brand->addProduct($productDb);
                            }
                            $output->writeln('Product creation >> ' . $sku);
                            $this->manager->flush();
                        } else {
                            $output->writeln('Do no exists in Business central '.$sku);
                        }
                    }
                    
                    $productSaleChannel = $productDb->getProductSaleChannelByCode('aliexpress_fitbit_es');
                    $productSaleChannel->setEnabled(true);
                    $productSaleChannel->setPrice($variant->sku_price);

                    if (property_exists($variant, "sku_discount_price")) {
                        $output->writeln('Add  promotion '.$sku.' >> '.$variant->sku_discount_price);
                        $promotion = new Promotion();
                        $promotion->setActive(true);
                        $promotion->setBeginDate($now);
                        $promotion->setEndDate($end);
                        $promotion->setDiscountType(Promotion::TYPE_FIXED);
                        $promotion->setFrequency(Promotion::FREQUENCY_CONTINUE);
                        $promotion->setFixedAmount($variant->sku_discount_price);
                        $promotion->setPriority(0);
                        $promotion->setComment('Import from Aliexpress Fitbit');
                        $productSaleChannel->addPromotion($promotion);
                    }

                    $this->manager->flush();
                }
            } else {
                $output->writeln('No product info');
            }
        }

        return 1;
    }



    public function getProductInfo($productId)
    {
        for ($i = 0; $i < 3; $i++) {
            $productInfo = $this->aliExpressApi->getProductInfo($productId);
            if ($productInfo) {
                return $productInfo;
            } else {
                sleep(2);
            }
        }
        return null;
    }



    protected function extractBrandFromResponse($productInfo)
    {
        foreach ($productInfo->aeop_ae_product_propertys->global_aeop_ae_product_property as $skuList) {
            if ($skuList->attr_name = 'Brand Name') {
                return strtoupper((string) $skuList->attr_value);
            }
        }
        return null;
    }


    private function getBusinessCentralProduct($sku)
    {
        return $this->bcConnector->getItemByNumber($sku);
    }



    private function getBrand(string $brandName): ?Brand
    {
        $nameSanitized =  strtoupper($brandName);
        if (strlen($nameSanitized) == 0) {
            return null;
        }
        $brand = $this->manager->getRepository(Brand::class)->findOneBy(['name' => $nameSanitized]);
        if (!$brand) {
            $brand = new Brand();
            $brand->setName($nameSanitized);
            $this->manager->persist($brand);
        }
        return $brand;
    }
}
