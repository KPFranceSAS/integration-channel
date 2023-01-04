<?php

namespace App\Helper\Traits;

use App\Entity\WebOrder;

trait TraitServiceLog
{
    protected function addLogToOrder(WebOrder $webOrder, string $message)
    {
        $webOrder->addLog($message);
        $this->logger->info($message);
    }



    protected function addErrorToOrder(WebOrder $webOrder, string $message)
    {
        $webOrder->addError($message);
        $this->addError($webOrder . ' > ' . $message);
    }


    protected function addOnlyLogToOrderIfNotExists(WebOrder $webOrder, string $message)
    {
        if ($webOrder->haveNoLogWithMessage($message)) {
            $this->addLogToOrder($webOrder, $message);
        } else {
            $this->logger->info($message);
        }
    }


    protected function addOnlyErrorToOrderIfNotExists(WebOrder $webOrder, string $message)
    {
        if ($webOrder->haveNoLogWithMessage($message)) {
            $this->addErrorToOrder($webOrder, $message);
        } else {
            $this->logger->error($message);
        }
    }


    protected function addError(string $errorMessage)
    {
        $this->logger->error($errorMessage);
        $this->errors[] = $errorMessage;
    }


    protected function logLine($message)
    {
        $separator = str_repeat("-", strlen($message));
        $this->logger->info('');
        $this->logger->info($separator);
        $this->logger->info($message);
        $this->logger->info($separator);
    }
}
