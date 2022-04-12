<?php

namespace App\Command\Amazon\Import;

use App\Entity\AmazonFinancialEventGroup;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class ImportFinancialFilesCommand extends Command
{
    protected static $defaultName = 'app:amz-import-financial-files';
    protected static $defaultDescription = 'Import historical events from Amz';

    public function __construct(ManagerRegistry $manager)
    {
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('pathDirectory', InputArgument::REQUIRED, 'Path of the directories');
    }


    private $pathDirectory;

    private $manager;

    private $output;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->pathDirectory = $input->getArgument('pathDirectory');
        $directories = array_diff(scandir($this->pathDirectory), array('..', '.'));
        foreach ($directories as $directory) {
            $this->managerFolders($directory);
        }
        return Command::SUCCESS;
    }


    protected function managerFolders($directory)
    {
        $marketplace = $this->getMarketplace($directory);
        $this->output->writeln("##############################");
        $this->output->writeln('---------Start -------' . $marketplace);
        $this->output->writeln("##############################");
        $files = array_diff(scandir($this->pathDirectory . '/' . $directory), array('..', '.'));
        foreach ($files as $file) {
            $this->output->writeln('---------Start -------' . $file);
            $this->managerFiles($file);
            $this->output->writeln('----------End ----------' . $file);
            $this->output->writeln("");
        }
        $this->output->writeln("##############################");
        $this->output->writeln('---------End -------' . $marketplace);
        $this->output->writeln("##############################");
        $this->output->writeln("");
    }


    protected function managerFiles($file)
    {
        $eventGroupName = str_replace('.txt', '', $file);
        $this->output->writeln('Import financialEvent  for ' . $eventGroupName);
        $this->checkAlreadyImported($eventGroupName);
    }


    protected function checkAlreadyImported($eventGroupName)
    {
        $eventGroup = $this->manager->getRepository(AmazonFinancialEventGroup::class)->findOneBy(["financialEventId" => $eventGroupName]);
        if ($eventGroup) {
            foreach ($eventGroup->getAmazonFinancialEvents() as $amzFinc) {
                $this->manager->remove($amzFinc);
                $eventGroup->removeAmazonFinancialEvent($amzFinc);
            }
            $this->manager->flush();
        }
    }



    protected function import($eventGroupName)
    {
        $eventGroup = $this->manager->getRepository(AmazonFinancialEventGroup::class)->findOneBy(["financialEventId" => $eventGroupName]);
        if ($eventGroup) {
            foreach ($eventGroup->getAmazonFinancialEvents() as $amzFinc) {
                $this->manager->remove($amzFinc);
                $eventGroup->removeAmazonFinancialEvent($amzFinc);
            }
            $this->manager->flush();
        }
    }





    protected function getMarketplace($locale)
    {
        if ($locale == 'GB') {
            return "Amazon.co.uk";
        } else {
            return 'Amazon.' . strtolower($locale);
        }
    }
}
