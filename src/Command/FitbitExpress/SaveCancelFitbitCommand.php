<?php

namespace App\Command\FitbitExpress;

use App\Command\AliExpress\SaveCancelCommand;
use App\Entity\WebOrder;
use App\Service\BusinessCentral\GadgetIberiaConnector;
use App\Service\FitbitExpress\FitbitExpressApi;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SaveCancelFitbitCommand extends SaveCancelCommand
{
    protected static $defaultName = 'app:fitbitexpress-cancel-orders';
    protected static $defaultDescription = 'Retrieve all fitbitexpress orders cancelled online';


    public function __construct(ManagerRegistry $manager, FitbitExpressApi $fitbitExpressApi, LoggerInterface $logger, MailService $mailService, GadgetIberiaConnector $gadgetIberiaConnector)
    {
        parent::__construct($manager, $fitbitExpressApi, $logger, $mailService,  $gadgetIberiaConnector);
    }

    protected function getChannel()
    {
        return WebOrder::CHANNEL_FITBITEXPRESS;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return parent::execute($input, $output);
    }
}
