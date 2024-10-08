<?php

namespace App\Channels\Mirakl;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\Model\JournalLine;
use App\Channels\Mirakl\MiraklApiParent;
use App\Entity\MarketplaceInvoice;
use App\Entity\MarketplaceInvoiceLine;
use App\Entity\Settlement;
use App\Entity\SettlementTransaction;
use App\Entity\WebOrder;
use App\Helper\MailService;
use App\Helper\Traits\TraitServiceLog;
use App\Service\Aggregator\ApiAggregator;
use DateInterval;
use DateTime;
use DateTimeZone;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use Twig\Environment;

abstract class MiraklAccountingIntegrationParent
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
        protected Environment $twig
    ) {
        $this->manager = $manager->getManager();
    }

    abstract public function getChannel(): string;

    abstract protected function getCompanyIntegration(): string;

    abstract protected function getByDefaultCustomer(): string;

    abstract protected function getProviderNumber(): string;

    abstract protected function getBankNumber(): string;

    abstract protected function getJournalName(): string;

    abstract protected function getAccountNumberForFeesMarketplace(): string;


    protected function getMiraklApi(): MiraklApiParent
    {
        return $this->apiAggregator->getApi($this->getChannel());
    }



    public function integrateAllSettlements()
    {
        $settlements=$this->getAllSettlements();
        foreach ($settlements as $settlement) {
            $this->manageSettlement($settlement);
        }
    }



    public function integrateSpecificSettlement($idSettlement)
    {
        $invoices =  $this->getMiraklApi()->getAllInvoices();

        foreach ($invoices as $invoice) {
            if ($invoice['id']==$idSettlement) {
                $this->manageSettlement($invoice);
            }
            
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
            $this->createPurchaseInvoices($settlementDb);
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


    protected function createPurchaseInvoices(Settlement $settlementDb)
    {
        foreach ($settlementDb->getMarketplaceInvoices() as $marketplaceInvoice) {
            $purchaseOrderArray = $this->generatePurchaseOrder($marketplaceInvoice);
            $this->addLogToOrder($settlementDb, 'Creation in BC '.$this->getCompanyIntegration().' of purchase order');

            $connector = $this->getConnector();
            $orderBc=$connector->createPurchaseInvoice($purchaseOrderArray);

            $orderBc=$connector->updatePurchaseInvoice($orderBc['id'], '*', ['invoiceDate' => $settlementDb->getPostedDate()->format('Y-m-d')]);
            $this->addLogToOrder($settlementDb, 'Purchase order created to the vendor '.$orderBc['number']);
            $marketplaceInvoice->setErpDocumentNumber($orderBc['number']);
            
        }
    }



    protected function sendNoticeEmail(Settlement $settlement): bool
    {
        $pdf = $this->getMiraklApi()->getContentPdfDocumentForInvoice($settlement->getNumber());
        $attachment = ['content'=>$pdf, 'title'=>'invoice_'.$this->getChannel().'_'.$this->formateInvoiceNumber($settlement->getNumber()).'.pdf'];
        $contenu = $this->twig->render('email/integrationSetllement.html.twig', [
            'settlement' => $settlement,
        ]);
        $this->mailer->sendEmail('['.$this->getChannel().'] Payment integration '.$this->formateInvoiceNumber($settlement->getNumber()), $contenu, 'stephane.lanjard@kpsport.com', [$attachment]);
        $this->addLogToOrder($settlement, 'Email confirmation sent');
        $this->manager->flush();
        return true;
    }


    protected function registerAllTransactionsJournal(Settlement $settlement): void
    {
        $connector = $this->getConnector();
        $this->addAllSettlementsTransaction($settlement);
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
        $customerPayment->externalDocumentNumber = $this->formateInvoiceNumber($settlementTransaction->getSettlement()->getNumber());
        $customerPayment->comment = $this->getChannel(). ' '.$settlementTransaction->getReferenceNumber();
        $customerPayment->description =$settlementTransaction->getTransactionType() .' '.$settlementTransaction->getDocumentNumber();
        $customerPayment->documentType = 'Payment';
        $customerPayment->accountType = 'Bank account';
        return $customerPayment;
    }

    

    protected function addAllSettlementsTransaction(Settlement $settlement): void
    {
        $transactions = $this->getAllFiltreredTransaction($settlement);
        foreach ($transactions as $orderNumber => $transaction) {
            /** @var \App\Entity\WebOrder */
            $webOrder = $this->manager->getRepository(WebOrder::class)->findOneBy([
                'externalNumber'=>$orderNumber,
                'channel' => $this->getChannel()
            ]);

            if ($transaction['ORDER']>0) {
                $settlmentTransaction = $this->generateSettlementTransactionSaleOrder($transaction['ORDER'], $orderNumber, $webOrder);
                $settlement->addSettlementTransaction($settlmentTransaction);
            }

            if ($transaction['REFUND']<0) {
                $settlmentTransaction = $this->generateSettlementTransactionSaleRefund($transaction['REFUND'], $orderNumber, $webOrder);
                $settlement->addSettlementTransaction($settlmentTransaction);
            }
        }


        foreach ($settlement->getMarketplaceInvoices() as $marketplaceInvoice) {
            $transaction = $this->generateSettlementTransactionMarketplaceInvoiceInvoice($marketplaceInvoice);
            $settlement->addSettlementTransaction($transaction);
        }
    }



    protected function generateSettlementTransactionMarketplaceInvoiceInvoice(MarketplaceInvoice $marketplaceInvoice): SettlementTransaction
    {

        $transaction = new SettlementTransaction();
        $transaction->setTransactionType('Purchase order');
        $transaction->setReferenceNumber($marketplaceInvoice->getDocumentNumber());
        $transaction->setBcEntityType('Vendor');
        $transaction->setAmount(-$marketplaceInvoice->getTotalAmountWithTax());
        $transaction->setBcEntityNumber($this->getProviderNumber());
        $transaction->setDocumentNumber($marketplaceInvoice->getErpDocumentNumber());

        return $transaction;
    }



    protected function generateSettlementTransactionSaleRefund($amount, $orderNumber, ?Weborder $webOrder): SettlementTransaction
    {

        $settlmentTransaction = new SettlementTransaction();
        $settlmentTransaction->setAmount($amount);
        $settlmentTransaction->setTransactionType('Sale return');
        $settlmentTransaction->setReferenceNumber($orderNumber);
        $settlmentTransaction->setBcEntityType('Customer');
        
        if ($webOrder) {
            $settlmentTransaction->setBcEntityNumber($webOrder->getCustomerNumber());
            $saleReturn = $this->getSaleRefund($webOrder->getExternalNumber(),$webOrder->getInvoiceErp(), $amount );
            if ($saleReturn) {
                $settlmentTransaction->setDocumentNumber($saleReturn);
            } else {
                $settlmentTransaction->setDocumentNumber('NOT FOUND');
            }
        } else {
            $settlmentTransaction->setBcEntityNumber($this->getByDefaultCustomer());
            $settlmentTransaction->setDocumentNumber('NOT FOUND');
        }
        return $settlmentTransaction;
    }


    protected function getSaleRefund($externalDocument, $invoiceNumber, $amount){
        $saleReturns = $this->getConnector()->getSaleReturns("externalDocumentNo  eq '$externalDocument'");
        foreach($saleReturns as $saleReturn){
            if($saleReturn && $saleReturn['amountIncludingVAT'] == abs($amount)){
                return $saleReturn['no'];
            }
        }

        $saleReturns = $this->getConnector()->getSaleReturns("appliesToDocNo eq '$invoiceNumber' or correctInvoiceNo eq '$invoiceNumber'");
        foreach($saleReturns as $saleReturn){
            if($saleReturn && $saleReturn['amountIncludingVAT'] == abs($amount)){
                return $saleReturn['no'];
            }
        }
        

        $saleReturns = $this->getConnector()->getSaleMemos("externalDocumentNo  eq '$externalDocument'");
        foreach($saleReturns as $saleReturn){
            if($saleReturn && $saleReturn['totalAmountIncludingTax'] == abs($amount)){
                return $saleReturn['number'];
            }
        }

        return null;

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


    protected function getAllFiltreredTransaction(Settlement $settlement): array
    {
        $toIntegrate = [
            "REFUND_ORDER_SHIPPING_AMOUNT_TAX",
            "REFUND_ORDER_SHIPPING_AMOUNT",
            "REFUND_ORDER_AMOUNT_TAX",
            "REFUND_ORDER_AMOUNT",
            "ORDER_SHIPPING_AMOUNT_TAX",
            "ORDER_SHIPPING_AMOUNT",
            "ORDER_AMOUNT_TAX",
            "ORDER_AMOUNT",
        ];

        $transactions = $this->getMiraklApi()->getAllTransactionsForSettlementId($settlement->getInternalId());
        $filteredTransactions = [];
        foreach ($transactions as $transaction) {
            if (in_array($transaction['type'], $toIntegrate) && $transaction['amount']!=0) {
                $orderId = strtoupper($transaction['entities']['order'][$this->getKeyToCheck()]);
                $field = substr($transaction['type'], 0, 5)=='ORDER' ? 'ORDER' : 'REFUND';
                if (!array_key_exists($orderId, $filteredTransactions)) {
                    $filteredTransactions[$orderId]=['ORDER'=>0, 'REFUND'=>0];
                }
                $filteredTransactions[$orderId][$field] += $transaction['amount'];
               
            }
        }
        return $filteredTransactions;
    }


    protected function getKeyToCheck()
    {
        return 'id';
    }



    protected function getAllSettlements($from = 'P20D'): array
    {
        $dateEnd = new DateTime();
        $dateStart = (new DateTime())->sub(new DateInterval($from));
    
        return $this->getMiraklApi()->getAllInvoices([
            'start_date' => $dateStart,
            'end_date' => $dateEnd
        ]);
    }


    protected function canBeIntegrated(array $invoice): bool
    {
        $invoice = $this->manager->getRepository(Settlement::class)->findOneBy([
                'channel'=>$this->getChannel(),
                'number' => $invoice['invoice_id']
        ]);
        return $invoice === null;
    }


    protected function generateSettlementEntity(array $invoice): Settlement
    {
        $settlement = new Settlement();
        $settlement->setStatus(Settlement::CREATION);
        $settlement->setChannel($this->getChannel());
        $settlement->setPostedDate(new DateTime($invoice['issue_date'], new DateTimeZone('UTC')));
        $settlement->setStartDate(new DateTime($invoice['start_date'], new DateTimeZone('UTC')));
        $settlement->setEndDate(new DateTime($invoice['end_date'], new DateTimeZone('UTC')));
        $settlement->setDueDate(new DateTime($invoice['due_date'], new DateTimeZone('UTC')));
        $settlement->setNumber($invoice['invoice_id']);
        $settlement->setInternalId($invoice['id']);
        $settlement->setTotalAmount($invoice['summary']['amount_transferred']);
        $settlement->setTotalCommissionsWithTax($invoice['summary']['total_commissions_incl_tax']);
        $settlement->setTotalRefundCommisionsWithTax($invoice['summary']['total_refund_commissions_incl_tax']);
        $settlement->setTotalOrders($invoice['summary']['total_payable_orders_incl_tax']);
        $settlement->setTotalRefunds($invoice['summary']['total_refund_orders_incl_tax']);
        $settlement->setTotalSubscriptions($invoice['summary']['total_subscription_incl_tax']);
        $settlement->setTotalTransfer($invoice['summary']['amount_transferred']);
        $settlement->setBank($invoice['payment_info']['bank_name']." ".$invoice['payment_info']['iban']);
        $this->addLogToOrder($settlement, 'Generation settlement from Api id'.$invoice['id']);

        $invoiceDb  = $this->generateMarketplaceInvoiceEntity($invoice);
        $settlement->addMarketplaceInvoice($invoiceDb);
        $this->addLogToOrder($settlement, 'Generation marketplace Invoice '.$invoiceDb->getDocumentNumber());
        return $settlement;
    }





    protected function generateMarketplaceInvoiceEntity(array $settlement): MarketplaceInvoice
    {
        $invoice = new MarketplaceInvoice();
        $invoice->setChannel($this->getChannel());
        $invoice->setTotalAmountWithTax($settlement['total_amount_incl_taxes']);
        $invoice->setTotalAmountNoTax($settlement['total_amount_excl_taxes']);
        $invoice->setTotalAmountTax($settlement['total_amount_incl_taxes']-$settlement['total_amount_excl_taxes']);
        $invoice->setDocumentNumber($this->formateInvoiceNumber($settlement['invoice_id']));
        $invoice->setVendorNumber($this->getProviderNumber());
        $invoice->setCompany($this->getCompanyIntegration());
        foreach ($settlement['accounting_documents_items'] as $documentItem) {
            $invoiceLine = new MarketplaceInvoiceLine();
            $invoiceLine->setDescription($documentItem['description']);
            $invoiceLine->setTotalAmountNoTax($documentItem['amount_excl_taxes']);
            $tax = 0;
            foreach ($documentItem['taxes'] as $taxe) {
                $tax+= $taxe['amount'];
            }
            $invoiceLine->setTotalAmountTax($tax);
            $invoiceLine->setTotalAmountWithTax($tax+$documentItem['amount_excl_taxes']);
            $invoice->addMarketplaceInvoiceLine($invoiceLine);
        }


        return $invoice;
    }




    protected function formateInvoiceNumber($invoiceNumber)
    {
        return sprintf("%012d", $invoiceNumber);
    }
    


    protected function getConnector()
    {
        return $this->businessCentralAggregator->getBusinessCentralConnector($this->getCompanyIntegration());
    }


    protected function generatePurchaseOrder(MarketplaceInvoice $marketplaceInvoice)
    {
        $connector = $this->getConnector();
        
        $purchaseInvoice = [
            
            'dueDate' => $marketplaceInvoice->getSettlement()->getPostedDate()->format('Y-m-d'),
            'vendorInvoiceNumber' => $marketplaceInvoice->getDocumentNumber(),
            'vendorNumber' => $this->getProviderNumber(),
            'payToVendorNumber' => $this->getProviderNumber(),
            'pricesIncludeTax' => true,
            'purchaseInvoiceLines' => [],
        ];

        $accountId = $connector->getAccountByNumber($this->getAccountNumberForFeesMarketplace());
        foreach ($marketplaceInvoice->getMarketplaceInvoiceLines() as $mkpLine) {
            $purchaseInvoice['purchaseInvoiceLines'][] = [
                "accountId" => $accountId['id'],
                "lineType" => "Account",
                "description" =>  $mkpLine->getDescription(),
                "unitCost" => $mkpLine->getTotalAmountWithTax(),
                "quantity" => 1
               ];
        }

        return $purchaseInvoice;

        
    
    }
    
    




   



}
