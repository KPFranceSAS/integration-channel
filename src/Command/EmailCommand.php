<?php

namespace App\Command;

use App\Service\MailService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EmailCommand extends Command
{
    protected static $defaultName = 'app:envoi-email';
    protected static $defaultDescription = 'Envoi emails';

    public function __construct(MailService $mailer){
       
        parent::__construct();
        $this->mailer=$mailer;

    }

    private $mailer;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument("email", InputArgument::REQUIRED, 'email recicpient');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {   
        $this->mailer->sendEmail('Test', '<p>Test Content</p><p>Datetime : '.date('dmY-His').'</p>', $input->getArgument('email'));
        return Command::SUCCESS;
    }
}


