<?php

namespace App\Service\Aggregator;

use Exception;
use Psr\Log\LoggerInterface;

abstract class Aggregator
{
    protected $services;

    protected $logger;


    public function __construct(
        iterable $services,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        foreach ($services as $service) {
            $this->services[$service->getChannel()] = $service;
        }
    }



    public function getService(string $channel)
    {
        if (array_key_exists($channel, $this->services)) {
            return $this->services[$channel];
        } else {
           $this->logger->info("Channel $channel is not related to any " . get_class($this));
           return null;
        }
    }



    public function getChannels()
    {
        return array_keys($this->services);
    }
}
