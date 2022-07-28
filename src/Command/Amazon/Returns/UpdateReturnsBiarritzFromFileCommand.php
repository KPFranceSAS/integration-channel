<?php

namespace App\Command\Amazon\Returns;

use App\Entity\AmazonRemovalOrder;
use App\Entity\FbaReturn;
use App\Entity\ProductCorrelation;
use App\Helper\Utils\CsvExtracter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateReturnsBiarritzFromFileCommand extends Command
{
    protected static $defaultName = 'app:update-returns-biarritz-from-file';
    protected static $defaultDescription = 'Update return from files';

    public function __construct(ManagerRegistry $manager, CsvExtracter $csvExtracter)
    {
        $this->manager = $manager->getManager();
        $this->csvExtracter = $csvExtracter;
        parent::__construct();
    }

    private $manager;

    private $csvExtracter;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
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
                        if (strlen($returnIntegrated['Collection_Order'])>0) {
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
