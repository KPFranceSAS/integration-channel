<?php

namespace App\Service\Amazon;

use App\Entity\AmazonFinancialEvent;
use App\Entity\AmazonOrder;
use App\Entity\AmazonReturn;
use App\Entity\FbaReturn;
use App\Service\BusinessCentral\KpFranceConnector;
use App\Service\MailService;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class AmzFbaReturn
{
    protected $mailer;

    protected $manager;

    protected $logger;

    protected $kpFranceConnector;

    public function __construct(
        LoggerInterface $logger,
        ManagerRegistry $manager,
        MailService $mailer,
        KpFranceConnector $kpFranceConnector
    ) {
        $this->logger = $logger;
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->kpFranceConnector = $kpFranceConnector;
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



    public function updateReturnsFbaCreated()
    {
        $fbaReturns = $this->manager->getRepository(FbaReturn::class)->findBy([
            'status' => FbaReturn::STATUS_CREATED
        ]);

        foreach ($fbaReturns as $fbaReturn) {
            $this->checkFbaReturn($fbaReturn);
        }
    }



    protected function checkFbaReturn(FbaReturn $fbaReturn)
    {
        // check if return on FBA
        $fbaReturns = $this->manager->getRepository(AmazonReturn::class)->findBy([
            'orderId' =>  $fbaReturn->getAmazonOrderId(),
            'sku' => $fbaReturn->getSku()
        ]);
    }


    protected function createReturns(AmazonFinancialEvent $amazonFinancialEvent): array
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
            $orderFba =   $this->manager->getRepository(AmazonOrder::class)
                                ->findOneBy(
                                    [
                                        'sku' => $amazonFinancialEvent->getSku(),
                                        'amazonOrderId' => $amazonFinancialEvent->getAmazonOrderId()
                                    ]
                                );
        }
        return $fabREturns;
    }


    protected function getAmazonReturnNotLinked()
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
