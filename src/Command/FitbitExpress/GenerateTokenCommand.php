<?php

namespace App\Command\FitbitExpress;

use App\Service\FitbitExpress\FitbitExpressApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateTokenCommand extends Command
{
    protected static $defaultName = 'app:fitbitexpress-generate-code';
    protected static $defaultDescription = 'Generate a new token';

    public function __construct(FitbitExpressApi $fitbitExpress)
    {
        $this->fitbitExpress = $fitbitExpress;
        parent::__construct();
    }

    private $fitbitExpress;

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('code', InputArgument::REQUIRED, 'code');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $token = $this->fitbitExpress->getNewAccessToken($input->getArgument('code'));
        var_dump($token['access_token']);
        return Command::SUCCESS;
    }
}
