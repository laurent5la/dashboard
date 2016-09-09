<?php namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Mapper\LogFactory;

abstract class Controller extends BaseController {

	use DispatchesCommands, ValidatesRequests;

    protected $logTime = array();

    /**
     * Called to log an error.
     * @param String $method
     * @param String $errorMessage
     * @param LogFactory $logFactory
     * @since 16.13
     * @author mvalenzuela
     */
    protected function logError($method, $errorMessage,  LogFactory &$logFactory)
    {
        $logMessage[__CLASS__ . '->' . $method] = $errorMessage;
        $logFactory->writeErrorLog($logMessage);
    }

    /**
     * Called to start timing for timing log
     * @param String $method
     */
    protected function timingStart($method)
    {
        $this->logTime[__CLASS__] = $method;
        $this->logTime['start'] = time();
    }

    /**
     * called to calculate elapsed time and write to timing log
     * @param LogFactory $logFactory
     */
    protected function timingEnd(LogFactory &$logFactory)
    {
        $this->logTime['end_time'] = time();
        $this->logTime['elapsed_time'] = $this->logTime['end_time'] - $this->logTime['start_time'];
        $logFactory->writeTimingLog($this->logTime);
    }

    /**
     * Called to log an error.
     * @param String $method
     * @param String $warningMessage
     * @param LogFactory $logFactory
     * @since 16.13
     * @author aprakash
     */
    protected function logWarning($method, $warningMessage,  LogFactory &$logFactory)
    {
        $logMessage[__CLASS__ . '->' . $method] = $warningMessage;
        $logFactory->writeWarningLog($logMessage);
    }

}
