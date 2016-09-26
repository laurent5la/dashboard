<?php namespace App\Exceptions;

use Exception;
use App\Traits\Logging;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler {

	use Logging;

	/**
	 * A list of the exception types that should not be reported.
	 *
	 * @var array
	 */
	protected $dontReport = [
		'Symfony\Component\HttpKernel\Exception\HttpException'
	];

	/**
	 * Report or log an exception.
	 *
	 * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
	 *
	 * @param  \Exception  $e
	 * @return void
	 */
	public function report(Exception $e)
	{
		if ($e instanceof ApplicationException) {
			$this->error($e->getMessage() . "\n" . $e->getTraceAsString());
		} else {
			//Handle uncaught third party exceptions.
			$this->error("***Uncaught Third Party Exception***");
			$this->error($e->getMessage() . "\n" . $e->getTraceAsString());
		}
	}

	/**
	 * Render an exception into an HTTP response.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Exception  $e
	 * @return \Illuminate\Http\Response
	 */
	public function render($request, Exception $e)
	{
		//TODO: Implement error rendering logic here.
		//i.e. Render full stack trace in qa, stg. Render error message only in prd.
		//Create error response for front end. e.g. {"code": 500, "error": "message", "trace":"${e->getTrace()}"}
		return parent::render($request, $e);
	}

}
