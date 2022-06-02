<?php

namespace App\Service\Amazon;

use App\Entity\AmazonFinancialEvent;
use App\Entity\FbaReturn;
use App\Service\MailService;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class AmzFbaReturn
{
    protected $mailer;

    protected $manager;

    protected $logger;

    public function __construct(LoggerInterface $logger, ManagerRegistry $manager, MailService $mailer)
    {
        $this->logger = $logger;
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
    }



    public function generateReturns()
    {
        $refunds = $this->getAllRefundsNotIntegrated();
        $amzFbaReturns = [];
        foreach ($refunds as $refund) {
            $amzFbaReturns = array_merge($amzFbaReturns, $this->createReturns($refund));
        }
        foreach ($amzFbaReturns as $amzFbaReturn) {
            $this->manager->persist($amzFbaReturn);
        }
        $this->manager->flush();
    }


    public function createReturns(AmazonFinancialEvent $amazonFinancialEvent)
    {
        $nbRefunds = $amazonFinancialEvent->getQtyPurchased() ?? 1;
        $fabREturns = [];
        for ($i = 0; $i < $nbRefunds; $i++) {
            $fbaReturn = new FbaReturn();
            $fbaReturn->setAdjustmentId($amazonFinancialEvent->getAdjustmentId());
            $fbaReturn->setAmazonOrderId($amazonFinancialEvent->getAmazonOrderId());
            $fbaReturn->setSellerOrderId($amazonFinancialEvent->getSellerOrderId());
            $fbaReturn->setLocalization(FbaReturn::LOCALIZATION_FBA);
            $fbaReturn->setStatus(FbaReturn::STATUS_CREATED);
            $fbaReturn->setSku($amazonFinancialEvent->getSku());
            $fbaReturn->setPostedDate(DateTimeImmutable::createFromMutable($amazonFinancialEvent->getPostedDate()));
            $fbaReturn->setProduct($amazonFinancialEvent->getProduct());
            $fbaReturn->addLog('Creation of refund through refund event');
            $fabREturns[] = $fbaReturn;
        }
        return $fabREturns;
    }



    public function checkReturnFba()
    {
        $refunds = $this->getAllRefundsNotIntegrated();
        $amzFbaReturns = [];
        foreach ($refunds as $refund) {
            $amzFbaReturns = array_merge($amzFbaReturns, $this->createReturns($refund));
        }
        foreach ($amzFbaReturns as $amzFbaReturn) {
            $this->manager->persist($amzFbaReturn);
        }
        $this->manager->flush();
    }





    public function getAllRefundsNotIntegrated()
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
