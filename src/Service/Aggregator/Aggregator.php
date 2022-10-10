<?php

namespace App\Service\Aggregator;

use Exception;

abstract class Aggregator
{
    protected $services;


    public function __construct(
        iterable $services
    ) {
        foreach ($services as $service) {
            $this->services[$service->getChannel()] = $service;
        }
    }



    public function getService(string $channel)
    {
        if (array_key_exists($channel, $this->services)) {
            return $this->services[$channel];
        } else {
            throw new Exception("Channel $channel is not related to any " . get_class($this));
        }
    }



    public function getChannels()
    {
        return array_keys($this->services);
    }
}
