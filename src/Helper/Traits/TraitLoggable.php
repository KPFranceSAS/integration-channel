<?php

namespace App\Helper\Traits;

use Doctrine\ORM\Mapping as ORM;

trait TraitLoggable
{
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON, nullable: true)]
    private $logs = [];


    public function getLastLog($level = null) : string
    {
        $limitation = 35;
        $logs  = $level ? $this->getLogsByLevel($level) : $this->logs;
        $log = end($logs);
        if ($log) {
            return strlen((string) $log['content']) >  $limitation
                ?  substr((string) $log['content'], 0, $limitation) . '...'
                : $log['content'];
        } else {
            return '';
        }
    }

    public function getLastLogByLevel($level): string
    {
        return $this->getLastLog($level);
    }



    public function checkIfMessageAlreadyAdded($logMessage): bool
    {
        return $this->haveNoLogWithMessage($logMessage) ? false : true;
    }



    public function haveNoLogWithMessage($logMessage): bool
    {
        foreach ($this->logs as $log) {
            if ($log['content'] == $logMessage) {
                return false;
            }
        }
        return true;
    }


    public function getLogsByLevel($level): array
    {
        $logLevels = [];
        foreach ($this->logs as $log) {
            if ($log['content']==$level) {
                $logLevels[]=$log;
            }
        }
        return $logLevels;
    }


    public function addLog($content, $level = 'info', $user = "system") : array
    {
        $this->logs[] = [
            'humanDate' => date('d-m-Y H:i:s'),
            'date' => date('Y-m-d H:i:s'),
            'content' => $content,
            'level' => $level,
            'user' => $user
        ];
        return $this->logs;
    }


    public function addSuccess($content)
    {
        $this->addLog($content, 'success');
    }


    public function addWarning($content)
    {
        $this->addLog($content, 'warning');
    }


    public function addError($content)
    {
        $this->addLog($content, 'error');
    }


    public function getErrorsLogs(): ?array
    {
        return $this->getLogsByType('error');
    }


    public function getSuccessLogs(): ?array
    {
        return $this->getLogsByType('success');
    }

    public function getWarningLogs(): ?array
    {
        return $this->getLogsByType('warning');
    }

    public function getWarningErrorLogs(): ?array
    {
        $filters = [];
        foreach ($this->logs as $log) {
            if (in_array($log['level'], ['error', 'warning'])) {
                $filters[] = $log;
            }
        }
        return $filters;
    }

    public function getLogsByType(string $type): ?array
    {
        $filters = [];
        foreach ($this->logs as $log) {
            if ($log['level'] == $type) {
                $filters[] = $log;
            }
        }
        return $filters;
    }




    public function getLogs(): ?array
    {
        return $this->logs;
    }

    public function setLogs(?array $logs): self
    {
        $this->logs = $logs;

        return $this;
    }
}
