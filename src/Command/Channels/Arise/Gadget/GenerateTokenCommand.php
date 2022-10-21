<?php

namespace App\Command\Channels\Arise\Gadget;

use App\Channels\Arise\AriseRequest;
use App\Channels\Arise\Gadget\GadgetApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateTokenCommand extends Command
{
    protected static $defaultName = 'app:arise-generate-code';
    protected static $defaultDescription = 'Generate a new token for Arise. https://auth.proyectoarise.com/apps/oauth/authorize?response_type=code&force_auth=true&redirect_uri=https://marketplace.kps-group.com/&client_id=500972';

    public function __construct(GadgetApi $ariseClient)
    {
        $this->ariseClient = $ariseClient;
        parent::__construct();
    }

    private $ariseClient;

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('code', InputArgument::REQUIRED, 'Provides the code to generate a token');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $request = new AriseRequest('/auth/token/create');
        $request->addApiParam('code', $input->getArgument('code'));
        dump($this->ariseClient->getClient()->execute($request, false));
        return Command::SUCCESS;
    }
}
