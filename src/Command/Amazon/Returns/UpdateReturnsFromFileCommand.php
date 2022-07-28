<?php

namespace App\Command\Amazon\Returns;

use App\Entity\FbaReturn;
use App\Entity\ProductCorrelation;
use App\Helper\Utils\CsvExtracter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateReturnsFromFileCommand extends Command
{
    protected static $defaultName = 'app:update-returns-from-file';
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
            "businessCentralDocument" => null
        ]);

        $output->writeln('Start nbFbaReturn not sellable' . count($fbaReturns));

        foreach ($fbaReturns as $fbaReturn) {
            $this->checkForFbaReturn($fbaReturn, $returnIntegrateds);
        }

        $this->manager->flush();
        return Command::SUCCESS;
    }




    public function checkForFbaReturn(FbaReturn $fbaReturn, array $returnIntegrateds)
    {
        if ($fbaReturn->getAmazonReturn()) {
            foreach ($returnIntegrateds as $returnIntegrated) {
                if ($returnIntegrated['External Document No_']==$fbaReturn->getAmazonOrderId() && $returnIntegrated['Package Tracking No_']==$fbaReturn->getLpn()) {
                    $fbaReturn->setBusinessCentralDocument($returnIntegrated['Document No_']);
                    return;
                }
            }
        }
    }
}
