<?php

namespace App\Channels\Shopify;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\Model\JournalLine;
use App\Entity\Settlement;
use App\Entity\SettlementTransaction;
use App\Entity\WebOrder;
use App\Helper\MailService;
use App\Helper\Traits\TraitServiceLog;
use App\Service\Aggregator\ApiAggregator;
use App\Service\Aggregator\IntegratorAggregator;
use DateInterval;
use DateTime;
use DateTimeZone;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use Twig\Environment;

abstract class ShopifyPaypalAccountingIntegrationParent
{



    /**@var EntityManager */
    protected $manager;

    use TraitServiceLog;


    public function __construct(
        ManagerRegistry $manager,
        protected LoggerInterface $logger,
        protected MailService $mailer,
        protected BusinessCentralAggregator $businessCentralAggregator,
        protected ApiAggregator $apiAggregator,
        protected IntegratorAggregator $integratorAggregator,
        protected Environment $twig
    ) {
        $this->manager = $manager->getManager();
    }

    abstract public function getChannel(): string;

    abstract protected function getCompanyIntegration(): string;

    abstract protected function getByDefaultCustomer(): string;

    abstract protected function getBankNumber(): string;

    abstract protected function getBankName(): string;
    

    abstract protected function getJournalName(): string;

    abstract protected function getAccountNumberForFeesMarketplace(): string;


    protected function getShopifyApi(): ShopifyApiParent
    {
        return $this->apiAggregator->getApi($this->getChannel());
    }



    protected function getSuffix(): string
    {
        return $this->getIntegrator()->getSuffixOrder();
    }



    protected function getIntegrator(): ShopifyIntegrateOrder
    {
        return $this->integratorAggregator->getIntegrator($this->getChannel());
    }



    public function integrateAllSettlements()
    {
       $orders = $this->getAllOrders();
       if(count($orders)==0){
           $this->logger->info('No orders to integrate');
           return;
       }

        
        $settlement = $this->generateSettlementEntity();
        
        $this->manager->persist($settlement);
        $this->manager->flush();

        $connector = $this->getConnector();

        $paypalFees = 0;
        $paypalReceived = 0;

        foreach($orders  as $order){
            $orderContent = $order->getOrderContent();
            $transactions = $this->getShopifyApi()->getAllTransactions($orderContent['id']);
            foreach($transactions as $transaction){
                if( $transaction['gateway'] =='paypal'){
                    $settlmentTransaction = $this->generateSettlementTransactionSaleOrder(floatval($transaction['amount']), $order);
                    $settlement->addSettlementTransaction($settlmentTransaction);
                    $paypalFees = $paypalFees + floatval($transaction['receipt']['fee_amount']);
                    $paypalReceived = $paypalReceived + floatval($transaction['receipt']['gross_amount']);
                }
            }
        }

        $settlement->setTotalAmount($paypalReceived);
        $settlement->setTotalCommissionsWithTax($paypalFees);
        $settlement->setTotalOrders($paypalReceived);
        $settlement->setTotalTransfer($paypalReceived-$paypalFees);

        $settlmentFee = $this->generateSettlementTransactionFee($settlement);
        $settlement->addSettlementTransaction($settlmentFee);

        $this->manager->flush();

        $customerJournal = $connector->getGeneralJournalByCode($this->getJournalName());
        if (!$customerJournal) {
            $settlement->addError('Not found General journal '.$this->getJournalName().' in '.$this->getCompanyIntegration());
            $this->manager->flush();
            return;
        }
        $this->addLogToOrder($settlement, 'Registration of all transactions in the journal  '.$this->getCompanyIntegration().' of purchase order');
        foreach ($settlement->getSettlementTransactions() as $transaction) {
            try {
                $customerPayment = $this->transformSettlementCustomerPayment($transaction);
                $settlement->addLog('Creation of transaction in journal '. json_encode($customerPayment->transformToArray()));
                $paymentBc=$connector->createJournalLine($customerJournal['id'], $customerPayment->transformToCreate());
                $paymentBc=$connector->updateJournalLine($customerJournal['id'], $paymentBc['id'], $paymentBc['@odata.etag'], $customerPayment->transformTo1stPatch());
                $paymentBc=$connector->updateJournalLine($customerJournal['id'], $paymentBc['id'], $paymentBc['@odata.etag'], $customerPayment->transformTo2ndPatch());
                $settlement->addLog('Transaction created in journal '. json_encode($paymentBc));
               
            } catch (Exception $e) {
                $message = mb_convert_encoding($e->getMessage(), "UTF-8", "UTF-8");
                $settlement->addError('Problem in creation transaction '.$this->getJournalName(). ' '.json_encode($customerPayment->transformToArray()).' >> '.$message);
            }
            $this->manager->flush();
           
        }

        $this->manager->flush();
        $this->sendNoticeEmail($settlement);
        $settlement->setStatus(Settlement::CREATED);
        $this->manager->flush();

    }








    public function getAllOrders()
    {
        $dateMin = new DateTime();
        $dateMin->sub(new DateInterval('P7D'));    
        $queryBuilder = $this->manager->createQueryBuilder();
        $query = $queryBuilder
            ->select('o')
            ->from(WebOrder::class, 'o')
            ->where('o.erpDocument = :source')
            ->andWhere('o.createdAt > :date')
            ->andWhere('o.channel = :channel')
            ->setParameter('channel', $this->getChannel())
            ->setParameter('date', $dateMin->format('Y-m-d H:i:s'))
            ->setParameter('source', WebOrder::DOCUMENT_INVOICE)
            ->getQuery();

        $orders = $query->getResult();
        $orderPaypals = [];
        foreach($orders as $order){
            if($this->canBeIntegrated($order)){
                $orderPaypals[] = $order;
            }
        }
        return $orderPaypals;
    }






    protected function sendNoticeEmail(Settlement $settlement): bool
    {
        $contenu = $this->twig->render('email/integrationSetllement.html.twig', [
            'settlement' => $settlement,
        ]);
        $this->mailer->sendEmail('['.$this->getChannel().'] Payment integration for payout #'.$settlement->getNumber(), $contenu, ['tesoreria@kp-group.eu', 'devops@kpsport.com']);
        $this->addLogToOrder($settlement, 'Email confirmation sent');
        $this->manager->flush();
        return true;
    }


    


    protected function transformSettlementCustomerPayment(SettlementTransaction $settlementTransaction) : JournalLine
    {
        $customerPayment = new JournalLine();
        $customerPayment->amount = $settlementTransaction->getAmount();
        $customerPayment->accountNumber = $this->getBankNumber();
        $customerPayment->balAccountType = $settlementTransaction->getBcEntityType();
        $customerPayment->BalAccountNo = $settlementTransaction->getBcEntityNumber();
        $customerPayment->postingDate = date('Y-m-d');
        $customerPayment->externalDocumentNumber = 'Payout #'.$settlementTransaction->getSettlement()->getNumber();
        $customerPayment->comment = $this->getChannel(). ' #'.$settlementTransaction->getReferenceNumber();
        $customerPayment->description =$settlementTransaction->getTransactionType() .' '.$settlementTransaction->getDocumentNumber();
        $customerPayment->documentType = 'Payment';
        $customerPayment->accountType = 'Bank account';
        return $customerPayment;
    }





    protected function generateSettlementTransactionSaleOrder($amount, Weborder $webOrder): SettlementTransaction
    {
        $settlmentTransaction = new SettlementTransaction();
        $settlmentTransaction->setAmount($amount);
        $settlmentTransaction->setTransactionType('Sale order');
        $settlmentTransaction->setReferenceNumber($webOrder->getExternalNumber());
        $settlmentTransaction->setBcEntityType('Customer');
        $settlmentTransaction->setBcEntityNumber($webOrder->getCustomerNumber());
        $settlmentTransaction->setDocumentNumber($webOrder->documentInErp());
        return $settlmentTransaction;
    }



    protected function generateSettlementTransactionFee(Settlement $settlement): SettlementTransaction
    {
        $transaction = new SettlementTransaction();
        $transaction->setTransactionType('Fees');
        $transaction->setReferenceNumber('Fees Paypal payout #'.$settlement->getInternalId());
        $transaction->setBcEntityType('G/L Account');
        $transaction->setAmount(-$settlement->getTotalCommissionsWithTax());
        $transaction->setBcEntityNumber($this->getAccountNumberForFeesMarketplace());
        return $transaction;
    }


   

    protected function canBeIntegrated(WebOrder $webOrder): bool
    {
        $orderContent = $webOrder->getOrderContent();
        if(!in_array('paypal',$orderContent['payment_gateway_names'])){
        return false;
        }
        $invoices = $this->manager->getRepository(SettlementTransaction::class)->findBy([
                'documentNumber'=> $webOrder->getInvoiceErp(),
        ]);
        return count($invoices)==0;
    }


    protected function generateSettlementEntity(): Settlement
    {
       
        $settlement = new Settlement();
        $date = new DateTime();
        $code = 'PAYPAL'.$date->format('YmdHis');
        $settlement->setStatus(Settlement::CREATION);
        $settlement->setChannel($this->getChannel());
        $settlement->setPostedDate($date);
        $settlement->setStartDate($date);
        $settlement->setEndDate($date);
        $settlement->setDueDate($date);
        $settlement->setNumber($code);
        $settlement->setInternalId($code);
        $settlement->setTotalAmount(0);
        $settlement->setTotalCommissionsWithTax(0);
        $settlement->setTotalRefundCommisionsWithTax(0);
        $settlement->setTotalOrders(0);
        $settlement->setTotalRefunds(0);
        $settlement->setTotalSubscriptions(0);
        $settlement->setTotalTransfer(0);
        $settlement->setBank($this->getBankName());
        $this->addLogToOrder($settlement, 'Generation settlement from Api id'.$code);
        return $settlement;
    }


    protected function getConnector()
    {
        return $this->businessCentralAggregator->getBusinessCentralConnector($this->getCompanyIntegration());
    }


    
    




   



}
