<?php

namespace App\Factory;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use Config;

class LogFactory
{
    private $debug;
    public function __construct()
    {
        $this->debug = true;
    }

    public function writeInfoLog($logMessage)
    {
        if($this->debug) {
            // create a log channel for general info
            $errorLog = new Logger('info_log');
            $errorStreamer = new StreamHandler(base_path() . '/storage/logs/info.log', Logger::INFO);
            $errorLog->pushHandler($errorStreamer);
            $errorLog->addInfo(json_encode($logMessage));
        }
    }

    public function writeAvalaraLog($logMessage)
    {
        if($this->debug) {
            // create a log channel for avalara calls
            $avalaraLog = new Logger('avalara_log');
            $avalaraStreamer = new StreamHandler(base_path() . '/storage/logs/avalara.log', Logger::INFO);
            $avalaraLog->pushHandler($avalaraStreamer);
            $avalaraLog->addInfo(json_encode($logMessage));
        }
    }

    public function writeErrorLog($logMessage)
    {
        // create a log channel for errors
        $errorLog = new Logger('error_log');
        $errorStreamer = new StreamHandler(base_path() . '/storage/logs/errors.log', Logger::ERROR);
        $errorLog->pushHandler($errorStreamer);
        $errorLog->addError(json_encode($logMessage));
    }

    public function writeWarningLog($logMessage)
    {
        if($this->debug) {
            // create a log channel for warnings
            $warningLog = new Logger('warning_log');
            $warningStreamer = new StreamHandler(base_path() . '/storage/logs/warnings.log', Logger::WARNING);
            $warningLog->pushHandler($warningStreamer);
            $warningLog->addWarning(json_encode($logMessage));
        }
    }

    public function writeAPILog($method, $logTime, $url, $params, $response)
    {
        if($this->debug) {
            $logMessage = array();
            $logMessage['method'] = isset($method) ? $method : '';
            $logMessage['timing'] = isset($logTime) ? $logTime : '';
            $logMessage['url'] = isset($url) ? $url : '';
            if (isset($params['password'])) $params['password'] = '';
            if (isset($params['old_password'])) $params['old_password'] = '';
            if (isset($params['new_password'])) $params['new_password'] = '';
            $logMessage['params'] = isset($params) ? $params : '';
            $logMessage['meta'] = isset($response['meta']['code']) ? $response['meta']['code'] : '';

            if(isset($response['meta']['code']) and $response['meta']['code'] == 200)
                $logMessage['response'] = isset($response) ? $response : '';
            else
                $logMessage['response'] = isset($response['error'][0]) ? $response['error'][0] : '';

            $apiLog = new Logger('API CALL');
            $apiStreamer = new StreamHandler(base_path().'/storage/logs/api.log', Logger::API);
            $apiLog->pushHandler($apiStreamer);
            $apiLog->addDebug(json_encode($logMessage));
        }
    }

    public function writeIPLog($logMessage)
    {
        $ipLog = new Logger('ip_address');
        $ipStreamer = new StreamHandler(base_path() . '/storage/logs/ip_address.log', Logger::DEBUG);
        $ipLog->pushHandler($ipStreamer);
        $ipLog->addDebug(json_encode($logMessage));
    }

    public function writeCyberSourceLog($logMessage)
    {
        if($this->debug) {
            // create a log channel for Cyber Source.
            $cyberSourceLog = new Logger('cybersource_log');
            $CyberSourceStreamer = new StreamHandler(base_path() . '/storage/logs/cybersource.log', Logger::API);
            $cyberSourceLog->pushHandler($CyberSourceStreamer);
            $cyberSourceLog->addInfo(json_encode($logMessage));
        }
    }

    public function writeTimingLog($logMessage)
    {
        if($this->debug) {
            // create a log channel for Cyber Source.
            $timingLog = new Logger('timing_log');
            $timingStreamer = new StreamHandler(base_path() . '/storage/logs/timing.log', Logger::INFO);
            $timingLog->pushHandler($timingStreamer);
            $timingLog->addInfo(json_encode($logMessage));
        }
    }

    public function writeActivityLog($logMessage)
    {
        if($this->debug) {
            // create a log channel for writing user activity on bad requests for cart.
            $activityLog = new Logger('activity_log');
            $activityStreamer = new StreamHandler(base_path() . '/storage/logs/activity.log', Logger::ERROR);
            $activityLog->pushHandler($activityStreamer);
            $activityLog->addError(json_encode($logMessage));
        }
    }

    public function writeContentLog($logMessage)
    {
        if($this->debug) {
            //creates a log channel for logging contentful webhook data
            $contentLog = new Logger('content-changes');
            $contentStreamer = new StreamHandler(base_path() . '/storage/logs/content-changes.log', Logger::INFO);
            $contentLog->pushHandler($contentStreamer);
            $contentLog->addInfo(json_encode($logMessage));
        }
    }
}
