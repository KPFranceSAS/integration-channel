<?php

namespace App\Command\Lazada;

use App\Service\Lazada\LazadaClient;
use App\Service\Lazada\LazadaRequest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateTokenCommand extends Command
{
    protected static $defaultName = 'app:lazada-generate-code';
    protected static $defaultDescription = 'Generate a new token for Lazada.';

    public function __construct(LazadaClient $lazadaClient)
    {
        $this->lazadaClient = $lazadaClient;
        parent::__construct();
    }

    private $lazadaClient;

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('code', InputArgument::REQUIRED, 'Provides the code to generate a token');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $request = new LazadaRequest('/auth/token/create');
        $request->addApiParam('code', $input->getArgument('code'));
        var_dump($this->lazadaClient->execute($request));
        return Command::SUCCESS;
    }
}
