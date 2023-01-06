<?php

namespace App\Command\Amazon;

use App\BusinessCentral\Connector\KpFranceConnector;
use App\Entity\AmazonOrder;
use App\Entity\AmazonRemoval;
use App\Entity\AmazonRemovalOrder;
use App\Entity\IntegrationChannel;
use App\Entity\WebOrder;
use App\Helper\MailService;
use App\Service\Amazon\Returns\GenerateAmzFbaRemoval;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckAmazonRemovalCommand extends Command
{
    protected static $defaultName = 'app:amz-check-removal';
    protected static $defaultDescription = 'Build and change status amz';

    public function __construct(GenerateAmzFbaRemoval $generateAmzFbaRemoval)
    {
        $this->generateAmzFbaRemoval = $generateAmzFbaRemoval;
        parent::__construct();
    }


    private $generateAmzFbaRemoval;



    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $this->generateAmzFbaRemoval->process();
        return Command::SUCCESS;
    }



}
