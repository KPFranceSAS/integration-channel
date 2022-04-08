<?php

namespace App\Command\Utils;

use App\Service\MailService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendMailCommand extends Command
{
    protected static $defaultName = 'app:send-email';
    protected static $defaultDescription = 'Send email';

    public function __construct(
        MailService $mailService
    ) {
        $this->mailService = $mailService;
        parent::__construct();
    }


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('title', InputArgument::REQUIRED, 'Title of email')
            ->addArgument('content', InputArgument::REQUIRED, 'Content of email')
            ->addArgument('emailAddress', InputArgument::REQUIRED, 'email Address');
    }

    private $mailService;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->mailService->sendEmail($input->getArgument('title'), $input->getArgument('content'), $input->getArgument('emailAddress'));
        return Command::SUCCESS;
    }
}
