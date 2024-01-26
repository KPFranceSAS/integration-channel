<?php

namespace App\Command\Utils;

use App\Helper\MailService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:send-email', 'Send email')]
class SendMailCommand extends Command
{
    public function __construct(
        private readonly MailService $mailService
    ) {
        parent::__construct();
    }


    protected function configure(): void
    {
        $this
            
            ->addArgument('title', InputArgument::REQUIRED, 'Title of email')
            ->addArgument('content', InputArgument::REQUIRED, 'Content of email')
            ->addArgument('emailAddress', InputArgument::REQUIRED, 'email Address');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->mailService->sendEmail(
            $input->getArgument('title'),
            $input->getArgument('content'),
            $input->getArgument('emailAddress')
        );
        return Command::SUCCESS;
    }
}
