<?php

namespace App\Command\Pim;


use App\Service\BusinessCentral\KpFranceConnector;
use App\Service\Pim\AkeneoConnector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ErpNameIntegrationCommand extends Command
{
    protected static $defaultName = 'app:pim-erp-name-from-bc-to-pim';
    protected static $defaultDescription = 'Put the name from erp';

    public function __construct(KpFranceConnector $businessCentralConnector, AkeneoConnector $akeneoConnector)
    {
        $this->businessCentralConnector = $businessCentralConnector;
        $this->akeneoConnector = $akeneoConnector;
        parent::__construct();
    }

    private $businessCentralConnector;

    private $akeneoConnector;




    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = $this->akeneoConnector->getAllProducts();
        foreach ($products as $product) {
            if (!array_key_exists("erp_name", $product['values'])) {
                $articleBc = $this->businessCentralConnector->getItemByNumber($product['identifier']);
                $updateValue = [
                    "values" => [
                        'erp_name' => [
                            [
                                "locale" => null,
                                "scope" => null,
                                "data" => strtoupper($articleBc['displayName'])
                            ],
                        ]
                    ]
                ];
                $output->writeln('Product ' . $product['identifier']);
                $this->akeneoConnector->updateProduct($product['identifier'], $updateValue);
            }
        }
        return Command::SUCCESS;
    }
}
