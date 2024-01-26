<?php

namespace App\Command\Utils;

use App\Helper\MailService;
use App\Helper\Utils\ExchangeRateCalculator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:exchange-rates', 'Get exchange rates')]
class ExchangeRateCommand extends Command
{
    public function __construct(
        private readonly ExchangeRateCalculator $exchangeRateCalculator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        dump($this->exchangeRateCalculator->getConvertedAmount(2.81, 'GBP', '2023-10-13'));
        return Command::SUCCESS;
    }
}
