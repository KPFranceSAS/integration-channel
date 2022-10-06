<?php

namespace App\Service\Amazon\Returns;

use App\Entity\AmazonReimbursement;
use App\Entity\AmazonReturn;
use App\Entity\FbaReturn;
use App\BusinessCentral\Connector\KpFranceConnector;
use App\Service\MailService;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class CheckIntegrationFbaReturn
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


    public function checkIntegrationReturns()
    {
        $this->checkIntegrationReturnToSales();
        $this->checkIntegrationReturnToSold();
    }



    public function checkIntegrationReturnToSold()
    {
        $fbaReturns = $this->manager->getRepository(FbaReturn::class)->findBy([
            'close' => false,
            'status' => FbaReturn::STATUS_RETURN_TO_FBA_NOTSELLABLE,
        ]);

        foreach ($fbaReturns as $fbaReturn) {
            if ($fbaReturn->getAmazonReturn()) {
                $return = $this->getSaleReturnByLpnAndExternalNumber($fbaReturn->getLpn(), $fbaReturn->getAmazonOrderId(), $fbaReturn->getProduct()->getSku());
                if ($return) {
                    $fbaReturn->setBusinessCentralDocument($return);
                }
            }
        }

        $this->manager->flush();
    }




    public function checkIntegrationReturnToSales()
    {
        $fbaReturns = $this->manager->getRepository(FbaReturn::class)->findBy([
            'close' => false,
            'status' => FbaReturn::STATUS_RETURN_TO_SALE,
        ]);

        foreach ($fbaReturns as $fbaReturn) {
            if ($fbaReturn->getAmazonReturn()) {
                $return = $this->getSaleReturnByLpnAndExternalNumber($fbaReturn->getLpn(), $fbaReturn->getAmazonOrderId(), $fbaReturn->getProduct()->getSku());
                if ($return) {
                    $fbaReturn->setBusinessCentralDocument($return);
                    $fbaReturn->setClose(true);
                }
            }
        }

        $this->manager->flush();
    }


    protected function getSaleReturnByLpnAndExternalNumber($lpn, $externalNumber, $sku): ?string
    {
        $saleReturn = $this->kpFranceConnector->getSaleReturnByLpnAndExternalNumber($lpn, $externalNumber);
        if ($saleReturn) {
            foreach ($saleReturn['salesReturnOrderLines'] as $returnLine) {
                if ($returnLine["number"]==$sku) {
                    return $saleReturn['number'];
                }
            }
        }
        return null;
    }
}
