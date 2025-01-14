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

abstract class ShopifyAccountingIntegrationParent
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



    public function integrateAllSettlements( $params)
    {
       

        $payouts = $this->getShopifyApi()->getPayouts($params);
        foreach ($payouts as $payout) {
            $this->manageSettlement($payout);
        }
    }






    protected function manageSettlement(array $settlementApi): bool
    {

        $this->logger->info('Treatment '.$settlementApi['id']);
        if ($this->canBeIntegrated($settlementApi)) {
            $this->logger->info('can be integrated '.$settlementApi['id']);
            $settlementDb = $this->generateSettlementEntity($settlementApi);
            
            $this->manager->persist($settlementDb);
            $this->manager->flush();
            $this->addAllSettlementsTransaction($settlementDb);
            $this->manager->flush();

            $this->registerAllTransactionsJournal($settlementDb);
            $this->manager->flush();
            $this->sendNoticeEmail($settlementDb);
            $settlementDb->setStatus(Settlement::CREATED);
            $this->manager->flush();

            return true;
        } else {
            $this->logger->info('Already integrated '.$settlementApi['id']);
            return false;
        }
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


    protected function registerAllTransactionsJournal(Settlement $settlement): void
    {
        $connector = $this->getConnector();
            
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
               
            } catch (Exception $e){
                $message = mb_convert_encoding($e->getMessage(), "UTF-8", "UTF-8");
                $settlement->addError('Problem in creation transaction '.$this->getJournalName(). ' '.json_encode($customerPayment->transformToArray()).' >> '.$message);
            }
            $this->manager->flush();
           
        }
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






    

    

    protected function addAllSettlementsTransaction(Settlement $settlement): void
    {
        $transactions = $this->getShopifyApi()->getAllShopifyPaiements(["payout_id"=>$settlement->getInternalId()]);
        foreach ($transactions as $transaction) {

            if($transaction['type'] == 'charge'){

                $order = $this->getShopifyApi()->getOrderById($transaction['source_order_id']);
                $orderNumber = $this->getSuffix().$order['order_number'];

                    /** @var \App\Entity\WebOrder */
                $webOrder = $this->manager->getRepository(WebOrder::class)->findOneBy([
                    'externalNumber'=>$orderNumber,
                    'channel' => $this->getChannel()
                ]);
                $settlmentTransaction = $this->generateSettlementTransactionSaleOrder($transaction['amount'], $orderNumber , $webOrder);
                $settlement->addSettlementTransaction($settlmentTransaction);
            } elseif ($transaction['type'] == 'refund') {
                $order = $this->getShopifyApi()->getOrderById($transaction['source_order_id']);
                $orderNumber = $this->getSuffix().$order['order_number'];
                $settlmentTransaction = $this->generateSettlementTransactionRefund($transaction['amount'], $orderNumber);
                $settlement->addSettlementTransaction($settlmentTransaction);
            } elseif ($transaction['type'] == 'dispute') {
                $order = $this->getShopifyApi()->getOrderById($transaction['source_order_id']);
                $orderNumber = $this->getSuffix().$order['order_number'];
                $settlmentTransaction = $this->generateSettlementTransactionDispute($transaction['amount'], $orderNumber);
                $settlement->addSettlementTransaction($settlmentTransaction);
            } elseif ($transaction['type'] != 'payout') {
                $settlmentTransaction = new SettlementTransaction();
                $settlmentTransaction->setAmount($transaction['amount']);
                $settlmentTransaction->setTransactionType($transaction['type']);
                $settlmentTransaction->setReferenceNumber($transaction['source_type'].' '.$transaction['source_id']);
                $settlmentTransaction->setBcEntityType('Customer');               
                $settlmentTransaction->setBcEntityNumber($this->getByDefaultCustomer());
                $settlmentTransaction->setDocumentNumber($transaction['source_id']);
            }

            
        }

        if($settlement->getTotalFees()!=0){
            $settlmentFee = $this->generateSettlementTransactionFee($settlement);
            $settlement->addSettlementTransaction($settlmentFee);
        }

        

    }



    protected function generateSettlementTransactionSaleOrder($amount, $orderNumber, ?Weborder $webOrder): SettlementTransaction
    {
        $settlmentTransaction = new SettlementTransaction();
        $settlmentTransaction->setAmount($amount);
        $settlmentTransaction->setTransactionType('Sale order');
        $settlmentTransaction->setReferenceNumber($orderNumber);
        $settlmentTransaction->setBcEntityType('Customer');               
        if ($webOrder) {
            $settlmentTransaction->setBcEntityNumber($webOrder->getCustomerNumber());
            $settlmentTransaction->setDocumentNumber($webOrder->documentInErp());
        } else {
            $settlmentTransaction->setBcEntityNumber($this->getByDefaultCustomer());
            $settlmentTransaction->setDocumentNumber('NOT FOUND');
        }
        return $settlmentTransaction;
    }


    protected function generateSettlementTransactionRefund($amount, $orderNumber): SettlementTransaction
    {
        $settlmentTransaction = new SettlementTransaction();
        $settlmentTransaction->setAmount($amount);
        $settlmentTransaction->setTransactionType('Refund');
        $settlmentTransaction->setReferenceNumber($orderNumber);
        $settlmentTransaction->setBcEntityType('Customer');               
        $settlmentTransaction->setBcEntityNumber($this->getByDefaultCustomer());
        $settlmentTransaction->setDocumentNumber($orderNumber);
        return $settlmentTransaction;
    }


    protected function generateSettlementTransactionDispute($amount, $orderNumber): SettlementTransaction
    {
        $settlmentTransaction = new SettlementTransaction();
        $settlmentTransaction->setAmount($amount);
        $settlmentTransaction->setTransactionType('Chargeback');
        $settlmentTransaction->setReferenceNumber($orderNumber);
        $settlmentTransaction->setBcEntityType('Customer');               
        $settlmentTransaction->setBcEntityNumber($this->getByDefaultCustomer());
        $settlmentTransaction->setDocumentNumber($orderNumber);
        return $settlmentTransaction;
    }




    


    protected function generateSettlementTransactionFee(Settlement $settlement): SettlementTransaction
    {

        $fees = $settlement->getTotalFees();

        $transaction = new SettlementTransaction();
        $transaction->setTransactionType('Fees');
        $transaction->setReferenceNumber('Fees shopify payout #'.$settlement->getInternalId());
        $transaction->setBcEntityType('G/L Account');
        $transaction->setAmount(-$fees);
        $transaction->setBcEntityNumber($this->getAccountNumberForFeesMarketplace());
        return $transaction;
    }


   

    protected function canBeIntegrated(array $invoice): bool
    {
        $invoice = $this->manager->getRepository(Settlement::class)->findOneBy([
                'channel'=>$this->getChannel(),
                'number' => $invoice['id']
        ]);
        return $invoice === null;
    }


    protected function generateSettlementEntity(array $invoice): Settlement
    {
        $settlement = new Settlement();
        $date = DateTime::createFromFormat('Y-m-d', $invoice['date']);
        $settlement->setStatus(Settlement::CREATION);
        $settlement->setChannel($this->getChannel());
        $settlement->setPostedDate($date);
        $settlement->setStartDate($date);
        $settlement->setEndDate($date);
        $settlement->setDueDate($date);
        $settlement->setNumber($invoice['id']);
        $settlement->setInternalId($invoice['id']);
        $settlement->setTotalAmount($invoice['amount']);
        $settlement->setTotalCommissionsWithTax($invoice['summary']['charges_fee_amount']);
        $settlement->setTotalRefundCommisionsWithTax($invoice['summary']['refunds_fee_amount']);
        $settlement->setAdjustmentFees($invoice['summary']['adjustments_fee_amount']);
        $settlement->setReservedFundFees($invoice['summary']['reserved_funds_fee_amount']);
        $settlement->setRetriedPayoutFees($invoice['summary']['retried_payouts_fee_amount']);
        
        $settlement->setTotalOrders($invoice['summary']['charges_gross_amount']);
        $settlement->setTotalRefunds($invoice['summary']['refunds_gross_amount']);
        $settlement->setTotalSubscriptions(0);
        $settlement->setTotalTransfer($invoice['amount']);
        $settlement->setBank($this->getBankName());
        $this->addLogToOrder($settlement, 'Generation settlement from Api id'.$invoice['id']);
        return $settlement;
    }


    protected function getConnector()
    {
        return $this->businessCentralAggregator->getBusinessCentralConnector($this->getCompanyIntegration());
    }


    
    




   



}
