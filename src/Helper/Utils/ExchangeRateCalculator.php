<?php

namespace App\Helper\Utils;

use DateTime;
use Exception;
use GuzzleHttp\Client;

/**
 * Consumed data from https://exchangerate.host/#/
 */
class ExchangeRateCalculator
{
    const BASE_EURO = 'EUR';

    private $changes;


    private function initializeRates($currency)
    {
        $actualYear = date('Y');
        $guzzle = new Client();
        if (!$this->changes) {
            $this->changes = [];
        }

        $this->changes[$currency] = [];

        for ($i = 2019; $i <= $actualYear; $i++) {
            $response_json = file_get_contents("https://api.exchangerate.host/timeseries?start_date=$i-01-01&end_date=$i-12-31&base=EUR&symbols=$currency");
            if (false !== $response_json) {
                $rates = json_decode($response_json, true);
                if ($rates['success']) {
                    foreach ($rates['rates'] as $date => $rate) {
                        if (array_key_exists($currency, $rate)) {
                            $this->changes[$currency][$date] = $rate[$currency];
                        }
                    }
                }
            } else {
                throw new Exception('Exchange rate is not available');
            }
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
            return 1;
            //throw new Exception("Exchange rate in $currency is not available for  $dateFormate ");
        }
    }


    public function getConvertedAmount(float $amount, string $currency, string $date): float
    {
        if ($currency == self::BASE_EURO) {
            return $amount;
        } else {
            $rateForDay = $this->getRateForDay($currency, $date);
            return $amount / $rateForDay;
        }
    }


    public function getConvertedAmountDate(float $amount, string $currency, DateTime $date): float
    {
        return $this->getConvertedAmount($amount, $currency, $date->format('Y-m-d'));
    }


    public function getRate(string $currency, string $date): float
    {
        return ($currency == self::BASE_EURO) ? 1 : $this->getRateForDay($currency, $date);
    }


    public function getRateDate(string $currency, DateTime $date): float
    {
        return $this->getRate($currency, $date->format('Y-m-d'));
    }
}
