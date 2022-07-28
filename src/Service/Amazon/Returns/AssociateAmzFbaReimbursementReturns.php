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
use Exception;
use Psr\Log\LoggerInterface;

class AssociateAmzFbaReimbursementReturns
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



    public function associateToFbaReturns()
    {
        $this->errors=[];
        $events = $this->getAllEventsAssociated();
        $nbEvents = count($events);
        $this->logger->info("Total events ".$nbEvents);
        $i=0;   
        foreach ($events as $key => $event) {
            try{
                $i++;
                $this->logger->info(get_class($event)." #".$event->getId() .' ['.$key.'] '.$i.'/'.$nbEvents);
                if(get_class($event)==AmazonReimbursement::class){
                    $this->associateAmazonReimbursement($event);
                } else {
                    $this->associateAmazonReturn($event);
                }
            } catch (Exception $e){
                    $this->logger->critical($event.'>>'.$e->getMessage());
                    $this->errors[]= $event.'>>'.$e->getMessage();
            }
            $this->manager->flush();
        }


        if (count($this->errors) > 0) {
            $messageError = implode('<br/><br/>', array_unique($this->errors));
            $this->mailer->sendEmail('Erreur AMAZON FBA return', $messageError, 'stephane.lanjard@kpsport.com');
        }
    
    }





    protected function associateAmazonReimbursement(AmazonReimbursement $amazonReimbursement)
    {
        $fbaReturn = $this->getBestFbaReturnAmazonReimbursement($amazonReimbursement);
        $fbaReturn->setAmazonReimbursement($amazonReimbursement);
        $fbaReturn->addLog('Reimboursement by FBA'.$amazonReimbursement);
        $fbaReturn->setLocalization(FbaReturn::LOCALIZATION_FBA_REIMBURSED);
        $fbaReturn->setStatus(FbaReturn::STATUS_REIMBURSED_BY_FBA);
    }

    protected function associateAmazonReturn(AmazonReturn $amazonReturn)
    {
        $fbaReturn = $this->getBestFbaReturnAmazonReturn($amazonReturn);
        $fbaReturn->setAmazonReturn($amazonReturn);
        $disposition = $amazonReturn->getDetailedDisposition();       
        $fbaReturn->addLog('Found return in FBA '.$amazonReturn. ' with disposition'.$disposition.' and status '.$amazonReturn->getStatus());
        
        $fbaReturn->setAmzProductStatus($disposition);
        $fbaReturn->setLpn($amazonReturn->getLicensePlateNumber());
        if($amazonReturn->getStatus() == 'Reimbursed'){
            $fbaReturn->setLocalization(FbaReturn::LOCALIZATION_FBA_REIMBURSED);
            if(!$fbaReturn->getAmazonReimbursement()){
                $fbaReturn->setStatus(FbaReturn::STATUS_WAITING_REIMBURSED_BY_FBA);
            }
        } else {
            if ($disposition == 'SELLABLE') {
                $fbaReturn->setLocalization(FbaReturn::LOCALIZATION_FBA);
                $fbaReturn->setStatus(FbaReturn::STATUS_RETURN_TO_SALE);
                $fbaReturn->addLog('Product is sellable and put again in FBA');
            } else {
                $fbaReturn->setLocalization(FbaReturn::LOCALIZATION_FBA_REFURBISHED);
                $fbaReturn->setStatus(FbaReturn::STATUS_RETURN_TO_FBA_NOTSELLABLE);
                $fbaReturn->addLog('Product is not sellable and will be send back in Biarritz');
            }
        }
        
    }


    protected function getBestFbaReturnAmazonReturn(AmazonReturn $amazonReturn): FbaReturn {
        $fbaReturns = $this->manager
        ->getRepository(FbaReturn::class)
        ->findBy(
            [
             'amazonOrderId' => $amazonReturn->getOrderId(),
             'product'=> $amazonReturn->getProduct(),
             'amazonReturn' => null
            ],
            ['postedDate'=>'ASC']
        );

        if(count($fbaReturns)==0){
            throw new Exception('No FBA return for amazon return '.$amazonReturn);
        }

        if($amazonReturn->getStatus() == 'Reimbursed'){
            // check if a sale return is associated to one of return marked as Reimbursed.
            foreach($fbaReturns as $fbaReturn){
                if($fbaReturn->getAmazonReimbursement()){
                    return $fbaReturn;
                }
            }
        } else {
            foreach($fbaReturns as $fbaReturn){
                if($fbaReturn->getAmazonReimbursement()===null){
                    return $fbaReturn;
                }
            }
        }        
        return $fbaReturns[0];
    }


    protected function getBestFbaReturnAmazonReimbursement(AmazonReimbursement $reimbursement): FbaReturn {
        $fbaReturns = $this->manager
        ->getRepository(FbaReturn::class)
        ->findBy(
            [
             'amazonOrderId' => $reimbursement->getAmazonOrderId(),
             'product'=> $reimbursement->getProduct(),
             'amazonReimbursement' => null
            ],
            ['postedDate'=>'ASC']
        );

        if(count($fbaReturns)==0){
            throw new Exception('No FBA return for AmazonReimbursement '.$reimbursement);
        }

        // check if a sale return is associated to one of return marked as Reimbursed.
        foreach($fbaReturns as $fbaReturn){
            if($fbaReturn->getAmazonReturn() && $fbaReturn->getAmazonReturn()->getStatus()== 'Reimbursed'){
                return $fbaReturn;
            }
        }
        
        return $fbaReturns[0];
    }


    protected function getSaleReturnBusinessCentral($lpn): ?string
    {
        $saleReturn = $this->kpFranceConnector->getSaleReturnByPackageTrackingNo($lpn);
        return $saleReturn ? $saleReturn['number'] : null;
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


    protected function getAllEventsAssociated(): array {
        $events = [];
        
        
        $remboursements =  $this->getAllReimboursmentsNotAssociated();
        foreach($remboursements as $remboursement){
            if($this->checkRemboursementIsPrincipal($remboursement)===false){
                $this->logger->alert("Not an reimbursement of item");
            } else {
                $events[$remboursement->getApprovalDate()->format('Y-m-d H:i:s')]=$remboursement;
            }
        }


        $returns =  $this->getAllReturnsNotAssociated();
        foreach($returns as $return){
            $events[$return->getReturnDate()->format('Y-m-d H:i:s')]=$return;
        }

        ksort($events);
        return $events;
    }


    
    protected function getAllReturnsNotAssociated(): array {
        $qb = $this->manager->createQueryBuilder();
        $expr = $this->manager->getExpressionBuilder();
        $qb->select('amz')
                ->from('App\Entity\AmazonReturn', 'amz')
                ->andWhere($expr->notIn(
                    'amz.id',
                    $this->manager->createQueryBuilder()
                        ->select('amazonReturn.id')
                        ->from('App\Entity\FbaReturn', 'fba')
                        ->leftJoin('fba.amazonReturn', 'amazonReturn')
                        ->where('fba.amazonReturn IS NOT NULL')
                        ->getDQL()
                ));
        return $qb->getQuery()->getResult();
    }


    protected function getAllReimboursmentsNotAssociated(): array
    {
        $qb = $this->manager->createQueryBuilder();
        $expr = $this->manager->getExpressionBuilder();
        $qb->select('amz')
                ->from('App\Entity\AmazonReimbursement', 'amz')
                ->where('amz.reason = :reason')
                ->andWhere($expr->notIn(
                    'amz.id',
                    $this->manager->createQueryBuilder()
                        ->select('amazonReimbursement.id')
                        ->from('App\Entity\FbaReturn', 'fba')
                        ->leftJoin('fba.amazonReimbursement', 'amazonReimbursement')
                        ->where('fba.amazonReimbursement IS NOT NULL')
                        ->getDQL()
                ))
                ->setParameter('reason', 'CustomerReturn');
        return $qb->getQuery()->getResult();
    }



}
