<?php namespace App\Http\Controllers;

use App\Traits\Logging;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller extends BaseController {

	use DispatchesCommands, ValidatesRequests, Logging;

    protected $logTime = array();

    /**
     * Called to start timing for timing log
     * @param String $method
     */
    protected function timingStart($method)
    {
        $this->logTime[__CLASS__] = $method;
        $this->logTime['start_time'] = time();
    }

    /**
     * called to calculate elapsed time and write to timing log
     */
    protected function timingEnd()
    {
        $this->logTime['end_time'] = time();
        $this->logTime['elapsed_time'] = $this->logTime['end_time'] - $this->logTime['start_time'];
        $this->timing($this->logTime);
    }

    /**
     * Called to log an error.
     * @param String $method
     * @param String $warningMessage
     * @since 16.13
     * @author aprakash
     */
    protected function logWarning($method, $warningMessage)
    {
        $logMessage[__CLASS__ . '->' . $method] = $warningMessage;
        $this->warning($logMessage);
    }
}
