<?php

namespace App\Command\Amazon;

use App\Entity\Product;
use App\Entity\User;
use App\Helper\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;

#[\Symfony\Component\Console\Attribute\AsCommand('app:amz-send-alert-fba', 'Send alert stock FBA')]
class SendFbaAlertStockCommand extends Command
{
    protected $twig;

    public function __construct(ManagerRegistry $manager, private readonly MailService $mailService, Environment $twig)
    {
        $this->manager = $manager->getManager();
        $this->twig = $twig;
        parent::__construct();
    }

    private $manager;

    private $exports = [];

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = $this->manager->getRepository(Product::class)->findAll();
        $zones = ['Eu', 'Uk'];
        foreach ($products as $product) {
            foreach ($zones as $zone) {
                if ($product->{'needTobeAlert'.$zone}()) {
                    $this->addToReport(
                        $zone,
                        $product->getSku(),
                        $product->getDescription(),
                        $product->getBrandName(),
                        $product->{'getMinQtyFba' . $zone}(),
                        $product->{'getFba' . $zone . 'InboundStock'}(),
                        $product->{'getFba' . $zone . 'SellableStock'}(),
                        $product->getLaRocaBusinessCentralStock(),
                        $product->getUk3plBusinessCentralStock(),
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
        int $qtyStockLaRoca,
        int $qtyStock3plUk
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
            'qtyStock3plUk' => $qtyStock3plUk,
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
