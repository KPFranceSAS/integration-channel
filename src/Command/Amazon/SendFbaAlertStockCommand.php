<?php

namespace App\Command\Amazon;

use App\Entity\Product;
use App\Entity\User;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;

class SendFbaAlertStockCommand extends Command
{
    protected static $defaultName = 'app:amz-send-alert-fba';
    protected static $defaultDescription = 'Send alert stock FBA';

    protected $twig;

    public function __construct(ManagerRegistry $manager, MailService $mailService, Environment $twig)
    {
        $this->manager = $manager->getManager();
        $this->mailService = $mailService;
        $this->twig = $twig;
        parent::__construct();
    }

    private $manager;

    private $exports = [];

    private $mailService;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = $this->manager->getRepository(Product::class)->findAll();
        $zones = ['Eu', 'Uk'];
        foreach ($products as $product) {
            foreach ($zones as $zone) {
                if ($product->needTobeAlert($zone)) {
                    $this->addToReport(
                        $zone,
                        $product->getSku(),
                        $product->getDescription(),
                        $product->getBrandName(),
                        $product->{'getMinQtyFba' . $zone}(),
                        $product->{'getFba' . $zone . 'InboundStock'}(),
                        $product->{'getFba' . $zone . 'SellableStock'}(),
                        $product->getLaRocaBusinessCentralStock(),
                    );
                }
            }
        }

        if (count($this->exports) > 0) {
            $this->sendAlert();
        }

        return Command::SUCCESS;
    }


    protected function addToReport(
        string $zone,
        string $sku,
        string $productName,
        string $brand,
        int $minFbaQty,
        int $qtyFbaInbound,
        int $qtyFbaSellable,
        int $qtyStockLaRoca
    ) {
        $this->exports[] = [
            'zone' => $zone,
            'sku' =>  $sku,
            'productName' =>  $productName,
            'brand' => $brand,
            'minFbaQty' =>  $minFbaQty,
            'qtyFbaInbound' =>  $qtyFbaInbound,
            'qtyFbaSellable' =>  $qtyFbaSellable,
            'qtyStockLaRoca' => $qtyStockLaRoca,
        ];
    }

    /**
     */
    protected function sendAlert()
    {
        $emails = [];

        $users = $this->manager->getRepository(User::class)->findAll();
        foreach ($users as $user) {
            if ($user->hasRole('ROLE_AMAZON')) {
                $emails[] = $user->getEmail();
            }
        }

        $contenu = $this->twig->render('email/fbaReplenishment.html.twig', [
            'exports' => $this->exports,
        ]);

        $newTitre = '[Stock FBA Replenishment]';

        if (count($emails) > 0) {
            $this->mailService->sendEmail($newTitre, $contenu, $emails);
        } else {
            $this->mailService->sendEmail($newTitre, $contenu);
        }
    }
}
