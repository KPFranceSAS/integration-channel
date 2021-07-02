<?php

namespace App\Command;

use App\Service\ImportInvoice;
use Symfony\Component\Mime\Email;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EmailCommand extends Command
{
    protected static $defaultName = 'app:envoi-email';
    protected static $defaultDescription = 'Envoi emails';

    public function __construct(MailerInterface $mailer){
       
        parent::__construct();
        $this->mailer=$mailer;

    }

    private $mailer;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {   
        $email = (new Email())
        ->from('redmine.seriel@gmail.com')
        ->to('s.lanjard@xefi-bayonne.fr')
        ->subject('Time for Symfony Mailer!')
        ->text('Sending emails is fun again!')
        ->html('<p>See Twig integration for better HTML integration!</p>');

        $this->mailer->send($email);



        return Command::SUCCESS;
    }
}


