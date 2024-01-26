<?php

namespace App\Command\Amazon\Returns;

use App\Entity\AmazonRemovalOrder;
use App\Entity\FbaReturn;
use App\Helper\Utils\CsvExtracter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:update-returns-biarritz-from-file', 'Update return from files')]
class UpdateReturnsBiarritzFromFileCommand extends Command
{
    public function __construct(ManagerRegistry $manager, private readonly CsvExtracter $csvExtracter)
    {
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    private $manager;


    protected function configure(): void
    {
        $this
            
            ->addArgument('pathFile', InputArgument::REQUIRED, 'Path of the file for injecting correlation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pathFile = $input->getArgument('pathFile');
        $returnIntegrateds = $this->csvExtracter->extractAssociativeDatasFromCsv($pathFile);
        $output->writeln('Start imports ' . count($returnIntegrateds));
        $fbaReturns = $this->manager->getRepository(FbaReturn::class)->findBy([
            'status' => FbaReturn::STATUS_RETURN_TO_FBA_NOTSELLABLE,
        ]);

        $output->writeln('Start nbFbaReturn not sellable' . count($fbaReturns));

        foreach ($fbaReturns as $fbaReturn) {
            if ($fbaReturn->getAmazonReturn()) {
                foreach ($returnIntegrateds as $returnIntegrated) {
                    if ($returnIntegrated['license-plate-number']==$fbaReturn->getLpn() && $returnIntegrated['amazon-order-id']==$fbaReturn->getAmazonOrderId()) {
                        if (strlen((string) $returnIntegrated['Collection_Order'])>0) {
                            $removal = $this->manager->getRepository(AmazonRemovalOrder::class)->findOneBy([
                                'orderId' => $returnIntegrated['Collection_Order'],
                                'product' => $fbaReturn->getProduct(),
                            ]);
                            if ($removal) {
                                $removal->addFbaReturn($fbaReturn);
                            }
                        }
                        
                        if ($returnIntegrated['LaRoca']=='x') {
                            $fbaReturn->setLocalization(FbaReturn::LOCALIZATION_LAROCA);
                            $fbaReturn->setStatus(FbaReturn::STATUS_SENT_TO_LAROCA);
                        } else {
                            $fbaReturn->setLocalization(FbaReturn::LOCALIZATION_BIARRITZ);
                            $fbaReturn->setStatus(FbaReturn::STATUS_RETURN_TO_BIARRITZ);
                        }
                    }
                }
            }
        }
        $this->manager->flush();
        return Command::SUCCESS;
    }
}
