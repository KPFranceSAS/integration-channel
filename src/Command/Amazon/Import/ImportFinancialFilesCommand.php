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

#[\Symfony\Component\Console\Attribute\AsCommand('app:amz-import-financial-files', 'Import historical events from Amz')]
class ImportFinancialFilesCommand extends Command
{
    public function __construct(ManagerRegistry $manager, private readonly ExchangeRateCalculator $exchangeRateCalculator)
    {
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('pathDirectory', InputArgument::REQUIRED, 'Path of the directories');
    }

    private $pathDirectory;

    private $manager;

    private $output;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->pathDirectory = $input->getArgument('pathDirectory');
        $directories = array_diff(scandir($this->pathDirectory), ['..', '.']);
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
        $files = array_diff(scandir($this->pathDirectory . '/' . $directory), ['..', '.']);
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


    protected function addSql()
    {
        $sql = " UPDATE `amazon_financial_event_group` SET `marketplace`='Amazon.co.uk' WHERE currency_code ='GBP';
        UPDATE `amazon_financial_event_group` SET `marketplace` = 'Amazon.de' WHERE `amazon_financial_event_group`.`financial_event_id` = '-g9KjxzG_8FbxR8O0DpMW2YiwXfnZfXBM9bQmFln-1g';
        UPDATE `amazon_financial_event_group` SET `marketplace` = 'Amazon.de' WHERE `amazon_financial_event_group`.`financial_event_id` = 'khq82BbuQ2aZVJXyOYpGtvJ_NS2Wc1isON2lbsXFN5c';
        UPDATE `amazon_financial_event_group` SET `marketplace` = 'Amazon.de' WHERE `amazon_financial_event_group`.`financial_event_id` = '0S4BM_YWmdMXgQAF1C4FQZQ_447udgcn4o1yqHTG9Vs';

        UPDATE `amazon_financial_event_group` SET `marketplace` = 'Amazon.de' WHERE `amazon_financial_event_group`.`financial_event_id` = 'zv0Y2aWawBsvdgTmNImyggCfHeC2Xq0i57VXYvuTHk8';
        UPDATE `amazon_financial_event_group` SET `marketplace` = 'Amazon.de' WHERE `amazon_financial_event_group`.`financial_event_id` = 'o9kOqfWYG4_e4nqdn5LAz54VrENrESDhpGLGCqfA7eQ';
        UPDATE `amazon_financial_event_group` SET `marketplace` = 'Amazon.de' WHERE `amazon_financial_event_group`.`financial_event_id` = '37J0xVhJskqOnZJz7x8ZGu8YHAh8Y40S3RyPHNL5lsE';
        UPDATE `amazon_financial_event_group` SET `marketplace` = 'Amazon.de' WHERE `amazon_financial_event_group`.`financial_event_id` = 's6Swbkl6w8DWFTdkoH_vRFXnT6JNEt7QEt9GxfI_y7o';
        UPDATE `amazon_financial_event_group` SET `marketplace`='Amazon.se' WHERE currency_code ='SEK';
        UPDATE `amazon_financial_event_group` SET `marketplace`='Amazon.pl' WHERE currency_code ='PLN';

        UPDATE `amazon_financial_event_group` SET `marketplace` = 'Amazon.it' WHERE `amazon_financial_event_group`.`financial_event_id` = 'RTkDhdeDRwMeXUARQckCVbElnseRaG949FDyXyqZmzE';
        UPDATE `amazon_financial_event_group` SET `marketplace` = 'Amazon.es' WHERE `amazon_financial_event_group`.`financial_event_id` = 'SUqR5CsqXiTX-amDwkbmoot3nlV4jDNclhbabEa-w-I';
        UPDATE `amazon_financial_event_group` SET `marketplace` = 'Amazon.it' WHERE `amazon_financial_event_group`.`financial_event_id` = 'Vv8FEt6eNtenhGdcdjUBGOeGQg7Ze16o6q9cJN-gB6M';
        UPDATE `amazon_financial_event_group` SET `marketplace` = 'Amazon.fr' WHERE `amazon_financial_event_group`.`financial_event_id` = 'ohjtiETuAE_3eloXtalihZA6o15xoG-ROTyJEFd3C2M';


        UPDATE `amazon_financial_event_group` SET `marketplace` = 'Amazon.de' WHERE `amazon_financial_event_group`.`financial_event_id` = 'MxEYH8y_b4aKgxHIAuJ8kNq29PZ65CvG85w_a-A-28g';
        UPDATE `amazon_financial_event_group` SET `marketplace` = 'Amazon.es' WHERE `amazon_financial_event_group`.`financial_event_id` = '6m7ZJo8XWsl9M-Z_4d5Uhkih1G3Um7AFHX2lewVNjSQ';
        UPDATE `amazon_financial_event_group` SET `marketplace` = 'Amazon.fr' WHERE `amazon_financial_event_group`.`financial_event_id` = '_4qPRKb74Fsg6HiQ_7A75F7womFlfKBWZWXabz6rOkA';


        ";
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
            return 'Amazon.' . strtolower((string) $locale);
        }
    }



    protected function managerFiles($file, $directory, $marketplace)
    {
        $eventGroupName = str_replace('.txt', '', (string) $file);
        $this->output->writeln('Import financialEvent  for ' . $eventGroupName);
        $this->cleanBeforeImport($eventGroupName);
        $datas = $this->getDataFromFiles($directory . '/' . $file);
        $this->output->writeln('Import form files ' . count($datas));
        $eventGroup = $this->manager->getRepository(AmazonFinancialEventGroup::class)->findOneBy(["financialEventId" => $eventGroupName]);
        $currency = $this->getCurrency($marketplace);
        $this->output->writeln('Currency  ' . $currency . ' >>  Marketplace ' . $marketplace);
        $eventGroup->setMarketplace($marketplace);
        unset($datas[0]);
        foreach ($datas as $data) {
            $amzFinancialEvent = new AmazonFinancialEvent();
            $amzFinancialEvent->setAdjustmentId($data["adjustment-id"]);
            $amzFinancialEvent->setAmazonOrderId($data["order-id"]);
            $amzFinancialEvent->setPostedDate($this->convertDatetime($data["posted-date-time"]));
            $amount = floatval(str_replace(',', '.', (string) $data["amount"]));

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
        $skuSanitized = strtoupper((string) $sku);
        $productCorrelation = $this->manager->getRepository(ProductCorrelation::class)->findOneBy(['skuUsed' => $skuSanitized]);
        $sku = $productCorrelation ? $productCorrelation->getSkuErpBc() : $skuSanitized;

        $product = $this->manager->getRepository(Product::class)->findOneBy([
            "sku" => $sku
        ]);
        return $product;
    }


    protected function convertDatetime($value)
    {
        return DateTime::createFromFormat('d.m.Y H:i:s', substr((string) $value, 0, 19));
    }

    protected function getCurrency($value)
    {
        return str_ends_with((string) $value, 'uk') ? 'GBP' : 'EUR';
    }


    protected function convertEmptyToNull($value)
    {
        return strlen((string) $value) == 0 ? null : $value;
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
