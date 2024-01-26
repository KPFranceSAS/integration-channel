<?php

namespace App\Command\Channels\AliExpress\AliExpress;

use App\Channels\AliExpress\AliExpress\AliExpressApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:aliexpress-generate-code', 'Generate a new token')]
class GenerateTokenCommand extends Command
{
    public function __construct(private readonly AliExpressApi $aliExpress)
    {
        parent::__construct();
    }

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
