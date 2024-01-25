<?php

namespace App\Command\Utils;

use App\Entity\OrderLog;
use App\Entity\WebOrder;
use DateInterval;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use function Symfony\Component\String\u;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StoreErrorsLogCommand extends Command
{
    protected static $defaultName = 'app:store-errors-logs';
    protected static $defaultDescription = 'Store error logs';

    private $manager;

    public function __construct(ManagerRegistry $manager)
    {
        /** @var \Doctrine\ORM\EntityManagerInterface */
        $this->manager = $manager->getManager();
        parent::__construct();
    }


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('nbHours', InputArgument::OPTIONAL, 'nbHours', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $batchSize = 50;
        $i = 0;
        $newSaved = 0;
        $nbHours = $input->getArgument('nbHours');
        $dateUpdated = new DateTime();
        $dateUpdated->sub(new DateInterval('PT'.$nbHours.'H'));
        $dateUpdatedString = $dateUpdated->format('Y-m-d H:i:s');
        $q = $this->manager->createQuery('select ord from App\Entity\WebOrder ord where ord.updatedAt > :dateUpdatedString')
        ->setParameter('dateUpdatedString', $dateUpdatedString);
        foreach ($q->toIterable() as $webOrder) {
            $nbNew = $this->extractErrorsFromWebOrder($webOrder);
            $newSaved+= $nbNew;
            ++$i;
            if (($i % $batchSize) === 0) {
                $output->writeln("Saved  $newSaved error logs on ".$i." orders");
                $this->manager->flush();
                $this->manager->clear();
            }
        }
        $output->writeln("Saved $newSaved error logs added on ".$i." orders");
        $this->manager->flush();
        $this->manager->clear();
        return Command::SUCCESS;
    }


    protected function extractErrorsFromWebOrder(WebOrder $webOrder): int
    {
        $nbErrors = 0;

        foreach($webOrder->getErrorsLogs() as $log){
            $orderLog = $this->transformInOrderLog($webOrder, $log);
            $orderLogDb= $this->manager->getRepository(OrderLog::class)->findOneByUnicityHash($orderLog->getUnicityHash());
            if(!$orderLogDb){
                $this->manager->persist($orderLog);
                $nbErrors++;
            }
        }
        return $nbErrors;
    }


    protected function transformInOrderLog(WebOrder $webOrder, array $log): OrderLog
    {
        $orderLog = new OrderLog();
        $orderLog->setIntegrationChannel($webOrder->getChannel());
        if(array_key_exists("humanDate", $log)){
            $orderLog->setLogDate(Datetime::createFromFormat("Y-m-d H:i:s", $log["date"]));
        } else {
            $orderLog->setLogDate(Datetime::createFromFormat("d-m-Y H:i:s", $log["date"]));
        }        
        $orderLog->setMarketplace($webOrder->getSubchannel());
        $orderLog->setCategory($this->getCategory($log["content"]));
        $orderLog->setMarketplace($webOrder->getSubchannel());
        $orderLog->setOrderId($webOrder->getId());
        $orderLog->setOrderNumber($webOrder->getExternalNumber());
        $orderLog->setUnicityHash($webOrder->getId().'_'.$log["date"]);
        $orderLog->setDescription($log['content']);
        return $orderLog;
    }



    protected function getCategory($description): string
    {
        $strDescription = strtoupper((string) $description);
        if (u($strDescription)->containsAny(["ADDRESS LENGTH", "LENGTH OF THE STREET" ,'FORBIDDEN WORD'])) {
            return OrderLog::CATEGORY_LENGTH;
        } elseif(u($strDescription)->containsAny(["WAREHOUSE SHIPMENT HAS NOT BEEN CREATED"])) {
            return OrderLog::CATEGORY_DELAY_SHIPMENT_CREATION;
        } elseif(u($strDescription)->containsAny(["INVOICE SHOULD BE DONE", "INVOICE INTEGRATION SHOULD BE DONE"])) {
            return OrderLog::CATEGORY_DELAY_INVOICE;
        } elseif(u($strDescription)->containsAny(["SHIPPING SHOULD BE PROCESSED", "ERROR POSTING TRACKING NUMBER"])) {
            return OrderLog::CATEGORY_DELAY_SHIPPING;
        }  elseif(u($strDescription)->containsAny(["AZURE.COM"])) {
            return OrderLog::CATEGORY_ERP;
        } elseif(u($strDescription)->containsAny(["CURL", "API.CHANNELADVISOR.COM", "CLIENTESPARCEL.DHL.ES"])) {
            return OrderLog::CATEGORY_SYSTEM;
        } elseif(u($strDescription)->containsAny(["CANNOT BE FOUND", "MAPPING", "CORRELATION"])) {
            return OrderLog::CATEGORY_SKU;
        }
        return OrderLog::CATEGORY_OTHERS;
    }


}



