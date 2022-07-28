<?php

namespace App\Service\Amazon\Returns;

use App\Entity\AmazonFinancialEvent;
use App\Entity\AmazonOrder;
use App\Entity\AmazonReturn;
use App\Entity\FbaReturn;
use App\Helper\Utils\DatetimeUtils;
use App\Service\BusinessCentral\KpFranceConnector;
use App\Service\MailService;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class GenerateAmzFbaReturn
{
    protected $mailer;

    protected $manager;

    protected $logger;

    protected $kpFranceConnector;


    /*
    UPDATE fba_return SET status = 0, logs = "[]", localization = 'CLIENT', lpn= null, amz_product_status = null, amazon_removal_id =null, close = false, amazon_return_id=null, amazon_reimbursement_id = null, business_central_document = null   ;
    */
    public function __construct(
        LoggerInterface $logger,
        ManagerRegistry $manager,
        MailService $mailer,
        KpFranceConnector $kpFranceConnector
    ) {
        $this->logger = $logger;
        /** @var \Doctrine\ORM\EntityManagerInterface */
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->kpFranceConnector = $kpFranceConnector;
    }



    public function generateReturns()
    {
        $refunds = $this->getAllRefundsNotIntegrated();
        $this->cleanErrors();
        $amzFbaReturns = [];
        foreach ($refunds as $refund) {
            $amzFbaReturns = array_merge($amzFbaReturns, $this->createReturns($refund));
        }
        foreach ($amzFbaReturns as $amzFbaReturn) {
            $this->manager->persist($amzFbaReturn);
        }
        $this->manager->flush();
    }




    protected function cleanErrors()
    {
        $this->errors=[];
    }


    protected function addError($error)
    {
        $this->errors[]=$error;
        $this->logger->critical($error);
    }


    protected function createReturns(AmazonFinancialEvent $amazonFinancialEvent): array
    {
        $this->logger->info('Return '.$amazonFinancialEvent->getAmazonOrderId());
        $nbRefunds = $amazonFinancialEvent->getQtyPurchased() ?? 1;
        $principalCost = abs($amazonFinancialEvent->getAmount() / $nbRefunds);
        $commisionEvent = $this->manager->getRepository(AmazonFinancialEvent::class)->findOneBy(['amountDescription'=>'Commission', 'adjustmentId'=>$amazonFinancialEvent->getAdjustmentId()]);
        $commisionCost = $commisionEvent ? $commisionEvent->getAmount() / $nbRefunds : 0;
        $refundCommisionEvent = $this->manager->getRepository(AmazonFinancialEvent::class)->findOneBy(['amountDescription'=>'RefundCommission', 'adjustmentId'=>$amazonFinancialEvent->getAdjustmentId()]);
        $refundCommisionCost = $refundCommisionEvent ? abs($refundCommisionEvent->getAmount() / $nbRefunds) : 0;


        $fabREturns = [];
        for ($i = 0; $i < $nbRefunds; $i++) {
            $fbaReturn = new FbaReturn();
            $fbaReturn->setAdjustmentId($amazonFinancialEvent->getAdjustmentId());
            $fbaReturn->setAmazonOrderId($amazonFinancialEvent->getAmazonOrderId());
            $fbaReturn->setSellerOrderId($amazonFinancialEvent->getSellerOrderId());
            $fbaReturn->setLocalization(FbaReturn::LOCALIZATION_CLIENT);
            $fbaReturn->setMarketplaceName($amazonFinancialEvent->getMarketplaceName());
            $fbaReturn->setClose(false);
            $fbaReturn->setRefundCommission($commisionCost);
            $fbaReturn->setRefundPrincipal($principalCost);
            $fbaReturn->setCommissionOnRefund($refundCommisionCost);
            $fbaReturn->setStatus(FbaReturn::STATUS_WAITING_CUSTOMER);
            $fbaReturn->setSku($amazonFinancialEvent->getSku());
            $fbaReturn->setPostedDate(DateTimeImmutable::createFromMutable($amazonFinancialEvent->getPostedDate()));
            $fbaReturn->setProduct($amazonFinancialEvent->getProduct());
            $fbaReturn->addLog('Creation of refund through refund event');
            $fabREturns[] = $fbaReturn;

            $orderFba =   $this->manager->getRepository(AmazonOrder::class)
                                ->findOneBy(
                                    [
                                        'sku' => $amazonFinancialEvent->getSku(),
                                        'amazonOrderId' => $amazonFinancialEvent->getAmazonOrderId()
                                    ]
                                );

            if (!$orderFba) {
                $this->addError("Any order  > ".$amazonFinancialEvent->getAmazonOrderId().'and sku '. $amazonFinancialEvent->getSku().' has been found');
            } else {
                $isOUtOfDelay40Days = DatetimeUtils::isOutOfDelayDays($amazonFinancialEvent->getPostedDate(), 40, $orderFba->getPurchaseDate());
                if ($isOUtOfDelay40Days) {
                    $this->addError("Return accepts after 40 days  order  > ".$amazonFinancialEvent->getAmazonOrderId().'and sku '. $amazonFinancialEvent->getSku());
                    $fbaReturn->addLog("Return accepts after 40 days  order  > ".$amazonFinancialEvent->getAmazonOrderId().'and sku '. $amazonFinancialEvent->getSku(), 'error');
                }
            }
        }
        return $fabREturns;
    }




    protected function getAllRefundsNotIntegrated()
    {
        $qb = $this->manager->createQueryBuilder();
        $expr = $this->manager->getExpressionBuilder();
        $qb->select('amz')
            ->from('App\Entity\AmazonFinancialEvent', 'amz')
            ->where('amz.transactionType = :transactionType')
            ->andWhere('amz.amountDescription = :amountDescription')
            ->andWhere($expr->notIn(
                'amz.adjustmentId',
                $this->manager->createQueryBuilder()
                    ->select('fba.adjustmentId')
                    ->from('App\Entity\FbaReturn', 'fba')
                    ->getDQL()
            ))
            ->setParameter('transactionType', "RefundEvent")
            ->setParameter('amountDescription', "Principal");
        return $qb->getQuery()->getResult();
    }
}
