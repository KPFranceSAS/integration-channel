<?php

namespace App\Command\Amazon\Import;

use App\Entity\AmazonFinancialEvent;
use App\Entity\AmazonFinancialEventGroup;
use App\Entity\Product;
use App\Entity\ProductCorrelation;
use App\Helper\Utils\ExchangeRateCalculator;
use DateTime;
use DateTimeZone;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class ImportFinancialFilesCommand extends Command
{
    protected static $defaultName = 'app:amz-import-financial-files';
    protected static $defaultDescription = 'Import historical events from Amz';

    public function __construct(ManagerRegistry $manager, ExchangeRateCalculator $exchangeRateCalculator)
    {
        $this->manager = $manager->getManager();
        $this->exchangeRateCalculator = $exchangeRateCalculator;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('pathDirectory', InputArgument::REQUIRED, 'Path of the directories');
    }


    private $exchangeRateCalculator;

    private $pathDirectory;

    private $manager;

    private $output;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->pathDirectory = $input->getArgument('pathDirectory');
        $directories = array_diff(scandir($this->pathDirectory), array('..', '.'));
        foreach ($directories as $directory) {
            $this->managerFolders($directory);
        }
        return Command::SUCCESS;
    }


    protected function managerFolders($directory)
    {
        $marketplace = $this->getMarketplace($directory);
        $this->output->writeln("##############################");
        $this->output->writeln('---------Start -------' . $marketplace);
        $this->output->writeln("##############################");
        $files = array_diff(scandir($this->pathDirectory . '/' . $directory), array('..', '.'));
        foreach ($files as $file) {
            $this->output->writeln('---------Start -------' . $file);
            $this->managerFiles($file, $this->pathDirectory . '/' . $directory, $marketplace);
            $this->output->writeln('----------End ----------' . $file);
            $this->output->writeln("");
        }
        $this->output->writeln("##############################");
        $this->output->writeln('---------End -------' . $marketplace);
        $this->output->writeln("##############################");
        $this->output->writeln("");
    }





    protected function cleanBeforeImport($eventGroupName)
    {
        $eventGroup = $this->manager->getRepository(AmazonFinancialEventGroup::class)->findOneBy(["financialEventId" => $eventGroupName]);
        if ($eventGroup) {
            foreach ($eventGroup->getAmazonFinancialEvents() as $amzFinc) {
                $this->manager->remove($amzFinc);
                $eventGroup->removeAmazonFinancialEvent($amzFinc);
            }
            $this->manager->flush();
        }
    }



    protected function getDataFromFiles($file)
    {
        $contentFiles = file_get_contents($file);

        $datas = [];
        $contentArray =  explode("\n", $contentFiles);
        $header = explode("\t", array_shift($contentArray));
        foreach ($contentArray as $contentLine) {
            $values = explode("\t", $contentLine);
            if (count($values) == count($header)) {
                $datas[] = array_combine($header, $values);
            }
        }
        return $datas;
    }



    protected function getMarketplace($locale)
    {
        if ($locale == 'GB') {
            return "Amazon.co.uk";
        } else {
            return 'Amazon.' . strtolower($locale);
        }
    }



    protected function managerFiles($file, $directory, $marketplace)
    {
        $eventGroupName = str_replace('.txt', '', $file);
        $this->output->writeln('Import financialEvent  for ' . $eventGroupName);
        $this->cleanBeforeImport($eventGroupName);
        $datas = $this->getDataFromFiles($directory . '/' . $file);
        $this->output->writeln('Import form files ' . count($datas));
        $eventGroup = $this->manager->getRepository(AmazonFinancialEventGroup::class)->findOneBy(["financialEventId" => $eventGroupName]);
        $currency = $this->getCurrency($marketplace);
        $this->output->writeln('Currency  ' . $currency . ' >>  Marketplace ' . $marketplace);
        $eventGroup->setMarketplaceName($marketplace);
        unset($datas[0]);
        foreach ($datas as $data) {
            $amzFinancialEvent = new AmazonFinancialEvent();
            $amzFinancialEvent->setAdjustmentId($data["adjustment-id"]);
            $amzFinancialEvent->setAmazonOrderId($data["order-id"]);
            $amzFinancialEvent->setPostedDate($this->convertDatetime($data["posted-date-time"]));
            $amount = floatval(str_replace(',', '.', $data["amount"]));

            $amzFinancialEvent->setAmount($this->exchangeRateCalculator->getConvertedAmountDate($amount, $currency, $amzFinancialEvent->getPostedDate()));
            $amzFinancialEvent->setAmountCurrency($amount);
            $amzFinancialEvent->setAmountDescription($this->convertAmountDescription($data["amount-description"]));
            $amzFinancialEvent->setAmountType($data["amount-type"]);
            $amzFinancialEvent->setMarketplaceName($marketplace);
            $amzFinancialEvent->setOrderItemCode($this->convertEmptyToNull($data["promotion-id"]));


            $amzFinancialEvent->setPromotionId($this->convertEmptyToNull($data["promotion-id"]));
            $amzFinancialEvent->setQtyPurchased($this->convertEmptyToNull($data["quantity-purchased"]));
            $amzFinancialEvent->setSellerOrderId($this->convertEmptyToNull($data["merchant-order-id"]));
            $amzFinancialEvent->setShipmentId($this->convertEmptyToNull($data["shipment-id"]));

            $amzFinancialEvent->setTransactionType($this->convertTransactionType($data["transaction-type"]));

            $sku = $this->convertTransactionType($data["sku"]);
            if ($sku) {
                $amzFinancialEvent->setSku($sku);
                $amzFinancialEvent->setProduct($this->getProductBySku($sku));
            }


            $this->manager->persist($amzFinancialEvent);
            $eventGroup->addAmazonFinancialEvent($amzFinancialEvent);
        }

        $this->manager->flush();
        $this->manager->clear();
    }


    protected function getProductBySku($sku)
    {
        $skuSanitized = strtoupper($sku);
        $productCorrelation = $this->manager->getRepository(ProductCorrelation::class)->findOneBy(['skuUsed' => $skuSanitized]);
        $sku = $productCorrelation ? $productCorrelation->getSkuErp() : $skuSanitized;

        $product = $this->manager->getRepository(Product::class)->findOneBy([
            "sku" => $sku
        ]);
        return $product;
    }


    protected function convertDatetime($value)
    {
        return DateTime::createFromFormat('d.m.Y H:i:s', substr($value, 0, 19));
    }

    protected function getCurrency($value)
    {
        return substr($value, -2) == 'uk' ? 'GBP' : 'EUR';
    }


    protected function convertEmptyToNull($value)
    {
        return strlen($value) == 0 ? null : $value;
    }

    protected function convertAmountDescription($type)
    {
        if ($type == "Previous Reserve Amount Balance") {
            return 'ReserveCredit';
        } else if ($type == "Current Reserve") {
            return 'ReserveDebit';
        } else {
            return $type;
        }
    }



    protected function convertTransactionType($type)
    {
        if ($type == "Order") {
            return 'ShipmentEvent';
        } else if ($type == "other-transaction") {
            return 'AdjustmentEvent';
        } else if ($type == "Refund") {
            return 'RefundEvent';
        } else {
            return $type;
        }
    }
}
