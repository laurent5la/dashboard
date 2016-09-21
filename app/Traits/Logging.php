<?php
namespace App\Traits;

use App\Factory\LogFactory;

trait Logging
{
    private static $logFactory;

    protected function getLogFactoryInstance()
    {
        if (is_null(self::$logFactory)) {
            self::$logFactory = new LogFactory();
        }
        return self::$logFactory;
    }

    protected function info($message)
    {
        $logFactory = $this->getLogFactoryInstance();
        $logFactory->writeInfoLog($message);
    }

    protected function error($message)
    {
        $logFactory = $this->getLogFactoryInstance();
        $logFactory->writeErrorLog($message);
    }

    protected function warning($message)
    {
        $logFactory = $this->getLogFactoryInstance();
        $logFactory->writeWarningLog($message);
    }

    protected function api($method, $logTime, $url, $params, $response)
    {
        $logFactory = $this->getLogFactoryInstance();
        $logFactory->writeAPILog($method, $logTime, $url, $params, $response);
    }

    protected function ip($message)
    {
        $logFactory = $this->getLogFactoryInstance();
        $logFactory->writeIPLog($message);
    }

    protected function activity($message)
    {
        $logFactory = $this->getLogFactoryInstance();
        $logFactory->writeActivityLog($message);
    }

    protected function timing($message)
    {
        $logFactory = $this->getLogFactoryInstance();
        $logFactory->writeTimingLog($message);
    }
}