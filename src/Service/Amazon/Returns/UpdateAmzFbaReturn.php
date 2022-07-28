<?php

namespace App\Service\Amazon\Returns;

use App\Entity\AmazonFinancialEvent;
use App\Entity\AmazonOrder;
use App\Entity\AmazonReimbursement;
use App\Entity\AmazonReturn;
use App\Entity\FbaReturn;
use App\Service\BusinessCentral\KpFranceConnector;
use App\Service\MailService;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class UpdateAmzFbaReturn
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
        /** @var \Doctrine\ORM\EntityManagerInterface */
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->kpFranceConnector = $kpFranceConnector;
    }


    public function updateReturns()
    {
        $fbaReturns = $this->manager->getRepository(FbaReturn::class)->findBy([
            'close' => false
        ]);

        foreach ($fbaReturns as $fbaReturn) {
            $this->updateReturn($fbaReturn);
            $this->manager->flush();
        }
    }



    protected function updateReturn(FbaReturn $fbaReturn)
    {
        if (!$fbaReturn->getAmazonReturn()) {
            $return = $this->hasBeenReturnedToFba($fbaReturn);
            if ($return) {
                $this->logger->info('Found '.$return);
                $disposition = $return->getDetailedDisposition();
                $fbaReturn->setAmazonReturn($return);
                
                $fbaReturn->addLog('Found return in FBA '.$return. ' with disposition'.$disposition);
                
                $fbaReturn->setAmzProductStatus($disposition);
                $fbaReturn->setLpn($return->getLicensePlateNumber());
                if ($return->getStatus()=='Unit returned to inventory') {
                    if ($disposition == 'SELLABLE') {
                        $fbaReturn->setLocalization(FbaReturn::LOCALIZATION_FBA);
                        $fbaReturn->setStatus(FbaReturn::STATUS_RETURN_TO_SALE);
                        $fbaReturn->addLog('Product is sellable and put again in FBA');
                    } else {
                        $fbaReturn->setLocalization(FbaReturn::LOCALIZATION_FBA_REFURBISHED);
                        $fbaReturn->setStatus(FbaReturn::STATUS_RETURN_TO_FBA_NOTSELLABLE);
                        $fbaReturn->addLog('Product is not sellable and will be send back in Biarritz');
                    }
                } else {
                    $fbaReturn->setLocalization(FbaReturn::LOCALIZATION_FBA);
                    $fbaReturn->setStatus(FbaReturn::STATUS_RETURN_TO_SALE);
                    $fbaReturn->addLog('Product will be reimbursed by FBA');
                }
            }
        }
        if (!$fbaReturn->getAmazonReimbursement()) {
            $reimbursement = $this->hasBeenReimbursedByFba($fbaReturn);
            if ($reimbursement && $this->checkRemboursementIsPrincipal($reimbursement)) {
                $this->logger->info('Found '.$reimbursement);
                $fbaReturn->setAmazonReimbursement($reimbursement);
                $fbaReturn->addLog('Found reimboursement from FBA'.$reimbursement);
                $fbaReturn->setStatus(FbaReturn::STATUS_REIMBURSED_BY_FBA);
                $fbaReturn->setClose(true);
            }
        }
    }


    protected function checkRemboursementIsPrincipal(AmazonReimbursement $reimbursement)
    {
        $financials = $this->manager
                        ->getRepository(AmazonFinancialEvent::class)
                        ->findBy(
                            ['transactionType'=>'RefundEvent',
                             'amountType'=> 'ItemChargeAdjustmentList',
                             'amountDescription'=> 'Goodwill',
                             'amazonOrderId' => $reimbursement->getAmazonOrderId(),
                             'product'=> $reimbursement->getProduct()
                            ]
                        );
        if (count($financials)>0) {
            foreach ($financials as $financial) {
                if (abs($financial->getAmountCurrency())==abs($reimbursement->getAmountTotalCurrency())) {
                    return false;
                }
            }
        }
        return true;
    }
    


    protected function hasBeenReturnedToFba(FbaReturn $fbaReturn): ?AmazonReturn
    {
        // check if return on FBA
        $qb = $this->manager->createQueryBuilder();
        $expr = $this->manager->getExpressionBuilder();
        $qb->select('amz')
                ->from('App\Entity\AmazonReturn', 'amz')
                ->where('amz.product = :product')
                ->andWhere('amz.orderId = :orderId')
                ->andWhere($expr->notIn(
                    'amz.id',
                    $this->manager->createQueryBuilder()
                        ->select('amzReturn.id')
                        ->from('App\Entity\FbaReturn', 'fba')
                        ->leftJoin('fba.amazonReturn', 'amzReturn')
                        ->where('fba.amazonReturn IS NOT NULL')
                        ->getDQL()
                ))
                ->setParameter('product', $fbaReturn->getProduct())
                ->setParameter('orderId', $fbaReturn->getAmazonOrderId())
              ->setMaxResults(1)  ;
        return $qb->getQuery()->getOneOrNullResult();
    }



    protected function hasBeenReimbursedByFba(FbaReturn $fbaReturn): ?AmazonReimbursement
    {
        // check if return on FBA
        $qb = $this->manager->createQueryBuilder();
        $expr = $this->manager->getExpressionBuilder();
        $qb->select('amz')
                ->from('App\Entity\AmazonReimbursement', 'amz')
                ->where('amz.product = :product')
                ->andWhere('amz.amazonOrderId = :orderId')
                ->andWhere('amz.reason = :reason')
                ->andWhere($expr->notIn(
                    'amz.id',
                    $this->manager->createQueryBuilder()
                        ->select('amazonReimbursement.id')
                        ->from('App\Entity\FbaReturn', 'fba')
                        ->leftJoin('fba.amazonReimbursement', 'amazonReimbursement')
                        ->where('fba.amazonReimbursement IS NOT NULL')
                        ->getDQL()
                ))
                ->setParameter('product', $fbaReturn->getProduct())
                ->setParameter('orderId', $fbaReturn->getAmazonOrderId())
                ->setParameter('reason', 'CustomerReturn')
              ->setMaxResults(1)  ;
        return $qb->getQuery()->getOneOrNullResult();
    }


    protected function getSaleReturnBusinessCentral($lpn): ?string
    {
        $saleReturn = $this->kpFranceConnector->getSaleReturnByPackageTrackingNo($lpn);
        return $saleReturn ? $saleReturn['number'] : null;
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
}
