<?php

namespace App\Command\Channels\Mirakl\Boulanger;

use App\Channels\Mirakl\Boulanger\BoulangerApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectBoulangerCommand extends Command
{
    protected static $defaultName = 'app:connect-boulanger';
    protected static $defaultDescription = 'Connection to Boulanger';

    public function __construct(
        BoulangerApi $boulangerApi
    ) {
        $this->boulangerApi = $boulangerApi;
        parent::__construct();
    }

    private $boulangerApi;

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $messages = $this->boulangerApi->getMessages();
        $messageJsons = [];
        foreach($messages as $message) {
            $messageJsons[]=$this->boulangerApi->getMessage($message['id'])->toArray();
        }

        file_put_contents('messages_boulanger.json', json_encode($messageJsons));
        return Command::SUCCESS;
    }



}
