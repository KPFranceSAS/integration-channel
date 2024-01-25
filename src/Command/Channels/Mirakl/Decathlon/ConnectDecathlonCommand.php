<?php

namespace App\Command\Channels\Mirakl\Decathlon;

use App\BusinessCentral\Connector\KpFranceConnector;
use App\Channels\Mirakl\Decathlon\DecathlonApi;
use App\Channels\Mirakl\Decathlon\DecathlonSyncProduct;
use Mirakl\MCI\Shop\Request\Hierarchy\GetHierarchiesRequest;
use Mirakl\MMP\Shop\Request\Channel\GetChannelsRequest;
use Mirakl\MMP\Shop\Request\Offer\GetAccountRequest;
use Mirakl\MMP\Shop\Request\Order\Get\GetOrdersRequest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectDecathlonCommand extends Command
{
    protected static $defaultName = 'app:connect-decathlon';
    protected static $defaultDescription = 'Connection to Deacthlon';

    public function __construct(
        private readonly DecathlonApi $decathlonApi
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $messages = $this->decathlonApi->getMessages();
        $messageJsons = [];
        foreach($messages as $message) {
            $messageJsons[]=$this->decathlonApi->getMessage($message['id'])->toArray();
        }

        file_put_contents('messages_decathlon.json', json_encode($messageJsons));


        return Command::SUCCESS;
    }





}
