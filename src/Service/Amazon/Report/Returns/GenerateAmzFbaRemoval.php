<?php

namespace App\Service\Amazon\Returns;

use App\Entity\AmazonOrder;
use App\Entity\AmazonRemoval;
use App\Entity\AmazonRemovalOrder;
use App\Entity\User;
use App\Helper\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class GenerateAmzFbaRemoval
{
    protected $mailer;

    protected $errors;

    protected $manager;

    protected $logger;

    protected $twig;

    
    public function __construct(
        LoggerInterface $logger,
        ManagerRegistry $manager,
        Environment $twig,
        MailService $mailer
    ) {
        $this->logger = $logger;
        /** @var \Doctrine\ORM\EntityManagerInterface */
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public function process()
    {
        $this->generateBlankRemovals();
        $this->addAddtionalInfos();
        $this->updateStatus();
    }

    protected function generateBlankRemovals()
    {
        $removalOrdersBlanks = $this->manager->getRepository(AmazonRemovalOrder::class)->findBy(
            [
                'amazonRemoval' => null,
            ]
        );

        $grouped = [];
        foreach ($removalOrdersBlanks as $removalOrdersBlank) {
            
            $orderId = $removalOrdersBlank->getOrderId();
            if (!array_key_exists($orderId, $grouped)) {
                $grouped[$orderId] = [];
            }
            $grouped[$orderId][] = $removalOrdersBlank;
        }

        foreach ($grouped as $orderId => $orders) {
            $removalOrderBlank = $this->manager->getRepository(AmazonRemoval::class)->findOneByOrderId($orderId);
            if (!$removalOrderBlank) {
                $removalOrderBlank = new AmazonRemoval();
                $removalOrderBlank->setOrderId($orderId);
                $removalOrderBlank->setStatus(AmazonRemoval::CREATED);
                $removalOrderBlank->setNotifyedCreation(false);
                $removalOrderBlank->setNotifyedEnd(false);
                $this->manager->persist($removalOrderBlank);
            }

            foreach ($orders as $order) {
                $removalOrderBlank->addAmazonRemovalOrder($order);
                $removalOrderBlank->setOrderType($order->getOrderType());
                $removalOrderBlank->setRequestDate($order->getRequestDate());
            }
        }

        $this->manager->flush();
    }


    protected function addAddtionalInfos()
    {
        $removalOrders = $this->manager->getRepository(AmazonRemoval::class)->findAll();
        foreach ($removalOrders as $removalOrder) {
            $removalOrderFba = $this->manager->getRepository(AmazonOrder::class)->findOneByMerchantOrderId($removalOrder->getOrderId());
            if ($removalOrderFba) {
                $removalOrder->setShipCity($removalOrderFba->getShipCity());
                $removalOrder->setShipCountry($removalOrderFba->getShipCountry());
                $removalOrder->setShipPostalCode($removalOrderFba->getShipPostalCode());
                $removalOrder->setShipState($removalOrderFba->getShipState());
                $removalOrder->setAmazonOrderId($removalOrderFba->getAmazonOrderId());
            }
        }
        $this->manager->flush();
    }



    protected function updateStatus()
    {
        $removalOrders = $this->manager->getRepository(AmazonRemoval::class)->findAll();
        foreach ($removalOrders as $removalOrder) {
            $removalOrder->updateStatus();


            if ($removalOrder->getStatus() == AmazonRemoval::PENDING
            && $removalOrder->getOrderType()=='Return'
            && $removalOrder->isNotifyedCreation()==false) {
                $this->sendAlert($removalOrder);
            }
        }

        

        $this->manager->flush();
    }



    /**
     */
    protected function sendAlert(AmazonRemoval $removalOrder)
    {
        $emails = [];

        $users = $this->manager->getRepository(User::class)->findAll();
        foreach ($users as $user) {
            if ($user->hasRole('ROLE_AMAZON')) {
                $emails[] = $user->getEmail();
            }
        }

        if ($removalOrder->getShipCountry()=='GB') {
            $emails[] ='kyle@kpsport.com';
        } elseif ($removalOrder->getShipCountry()=='ES') {
            $emails[] ='xavi.montes@kpsport.com';
        }

        $contenu = $this->twig->render('email/amazonRemoval.html.twig', [
            'removal' => $removalOrder,
        ]);

        $newTitre = 'FBA Amazon Removal order';

        if (count($emails) > 0) {
            $this->mailer->sendEmail($newTitre, $contenu, $emails);
        } else {
            $this->mailer->sendEmail($newTitre, $contenu);
        }
        $removalOrder->setNotifyedCreation(true);
    }
}
