<?php

namespace App\Command\Channels\Mirakl\Boulanger;

use App\Channels\Mirakl\Boulanger\BoulangerApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:connect-boulanger', 'Connection to Boulanger')]
class ConnectBoulangerCommand extends Command
{
    public function __construct(
        private readonly BoulangerApi $boulangerApi
    ) {
        parent::__construct();
    }

  

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
