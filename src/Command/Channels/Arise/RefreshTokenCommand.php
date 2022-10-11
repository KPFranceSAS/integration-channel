<?php

namespace App\Command\Channels\Arise;

use App\Channels\Arise\AriseClient;
use App\Channels\Arise\AriseRequest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshTokenCommand extends Command
{
    protected static $defaultName = 'app:arise-refresh-token';
    protected static $defaultDescription = 'Refresh the new token and change acces token';

    public function __construct(AriseClient $ariseClient)
    {
        $this->ariseClient = $ariseClient;
        parent::__construct();
    }

    private $ariseClient;

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('refreshToken', InputArgument::REQUIRED, 'Provides the refresh to generate a long refreshToken');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $request = new AriseRequest('/auth/token/refresh');
        $request->addApiParam('refresh_token', $input->getArgument('refreshToken'));
        var_dump($this->ariseClient->execute($request, false));
        return Command::SUCCESS;
    }
}
