<?php

namespace App\Helper\Utils;

use DateTimeInterface;
use Psr\Log\LoggerInterface;

/**
 * Consumed data from https://exchangerate.host/#/
 */
class ExchangeRateCalculator
{
    final public const BASE_EURO = 'EUR';

    private $changes;

    public function __construct(private readonly LoggerInterface $logger, private $accessKeyExchangeRate)
    {
    }


    private function initializeRates($currency)
    {
        $actualYear = date('Y');
        if (!$this->changes) {
            $this->changes = [];
        }

        $this->changes[$currency] = [];

        for ($i = 2019; $i < $actualYear; $i++) {
            $this->addData("$i-01-01", "$i-12-31", $currency);
        }
        $this->addData("$actualYear-01-01", date('Y-m-d'), $currency);
    }


    private function addData($dateDebut, $dateFin, $currency)
    {
        $ch = curl_init("https://api.exchangerate.host/timeframe?start_date=$dateDebut&end_date=$dateFin&source=".self::BASE_EURO."&currencies=$currency&access_key=".$this->accessKeyExchangeRate);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->logger->info("Get excahnes rates   for $dateDebut to $dateFin");
        $response_json = curl_exec($ch);
        curl_close($ch);
        $rates = json_decode($response_json, true);
        if ($rates['success']) {
            foreach ($rates['quotes'] as $date => $rate) {
                if (array_key_exists(self::BASE_EURO.$currency, $rate)) {
                    $this->changes[$currency][$date] = $rate[self::BASE_EURO.$currency];
                }
            }
        } else {
            $this->logger->critical("Exchange rate in $currency is not available for $dateDebut to $dateFin ");
        }
    }


    private function getRateForDay(string $currency, string $dateFormate): float
    {
        if (!$this->changes || !array_key_exists($currency, $this->changes)) {
            $this->initializeRates($currency);
        }
        if (array_key_exists($dateFormate, $this->changes[$currency])) {
            return $this->changes[$currency][$dateFormate];
        } else {
            $this->logger->critical("Exchange rate in $currency is not available for  $dateFormate ");
            return 1;
            //throw new Exception("Exchange rate in $currency is not available for  $dateFormate ");
        }
    }


    public function getConvertedAmount(float $amount, string $currencyFrom, string $date): float
    {
        if ($amount == 0) {
            return 0;
        }
        if ($currencyFrom == self::BASE_EURO) {
            return $amount;
        } else {

            $rateForDay = $this->getRateForDay($currencyFrom, $date);
            return $amount / $rateForDay;
        }
    }


    public function getConvertedAmountDate(float $amount, string $currency, DateTimeInterface $date): float
    {
        return $this->getConvertedAmount($amount, $currency, $date->format('Y-m-d'));
    }


    public function getRate(string $currency, string $date): float
    {
        return ($currency == self::BASE_EURO) ? 1 : $this->getRateForDay($currency, $date);
    }


    public function getRateDate(string $currency, DateTimeInterface $date): float
    {
        return $this->getRate($currency, $date->format('Y-m-d'));
    }
}
