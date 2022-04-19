<?php

namespace App\Command\AliExpress;

use App\Service\AliExpress\AliExpressApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateTokenCommand extends Command
{
    protected static $defaultName = 'app:aliexpress-generate-code';
    protected static $defaultDescription = 'Generate a new token';

    public function __construct(AliExpressApi $aliExpress)
    {
        $this->aliExpress = $aliExpress;
        parent::__construct();
    }

    private $aliExpress;

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('code', InputArgument::REQUIRED, 'code');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $token = $this->aliExpress->getNewAccessToken($input->getArgument('code'));
        var_dump($token['access_token']);
        return Command::SUCCESS;
    }
}
