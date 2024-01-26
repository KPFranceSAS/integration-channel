<?php

namespace App\Command\Pim;

use Akeneo\Pim\ApiClient\Exception\UnprocessableEntityHttpException;
use App\BusinessCentral\Connector\KpFranceConnector;
use App\Service\Pim\AkeneoConnector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:pim-erp-name-from-bc-to-pim', 'Put the name from erp')]
class ErpNameIntegrationCommand extends Command
{
    public function __construct(private readonly KpFranceConnector $businessCentralConnector, private readonly AkeneoConnector $akeneoConnector)
    {
        parent::__construct();
    }




    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = $this->akeneoConnector->getAllProducts();
        foreach ($products as $product) {
            $articleBc = $this->businessCentralConnector->getItemByNumber($product['identifier']);


            if ($articleBc) {
                $updateValue = [
                    "values" => []
                ];
                if (!array_key_exists("erp_name", $product['values'])) {
                    $updateValue ['values']['erp_name'] = [
                                [
                                    "locale" => null,
                                    "scope" => null,
                                    "data" => strtoupper((string) $articleBc['displayName'])
                                ],
                        ];
                }

                if (!array_key_exists("upc", $product['values']) && array_key_exists("ean", $product['values'])) {
                    $updateValue ['values']['upc'] = $product['values']['ean'];
                }

                if (count($updateValue['values']) > 0) {
                    $output->writeln('Update Product ' . $product['identifier']);


                    try {
                        $this->akeneoConnector->updateProduct($product['identifier'], $updateValue);
                    } catch (UnprocessableEntityHttpException $e) {
                        foreach ($e->getResponseErrors() as $error) {
                            $output->writeln("<error>" . $error['property']
                                                . ':' . $error['message']
                                                . ">>>" . json_encode($updateValue) . "</error>");
                        }
                    }
                } else {
                    $output->writeln('Nothing Product ' . $product['identifier']);
                }
            } else {
                $output-> writeln('Product not found in BC ' . $product['identifier']);
            }
        }
        return Command::SUCCESS;
    }
}
