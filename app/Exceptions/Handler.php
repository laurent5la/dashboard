<?php namespace App\Exceptions;

use Exception;
use App\Traits\Logging;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Response;

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
			$this->error(get_class($e) . " thrown:\n" . $e->getJson());
            if ($previous = $e->getPrevious()) {
                $this->error(get_class($e) . " previous:\n" . $this->getGeneralExceptionJson($previous));
            }
		} else {
			//Handle uncaught third party exceptions.
			$this->error("***Uncaught Third Party Exception***");
			$this->error(get_class($e) . " thrown:\n" . $this->getGeneralExceptionJson($e));
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
        if ($e instanceof ApplicationException) {
            $response = new Response();
            $response->setContent($e->getJson());
            $response->setStatusCode($e->getCode());
            return $response;
        } else {
            $response = new Response();
            $response->setContent($this->getGeneralExceptionJson($e));
            $response->setStatusCode($e->getCode());
            return $response;
        }
	}

    /**
     * Returns json string of general exception that does not have getJson() method.
     *
     * @param Exception $e
     * @return string
     */
    private function getGeneralExceptionJson(\Exception $e)
    {
        return json_encode([
            "message" => $e->getMessage(),
            "code" => $e->getCode(),
            "file" => $e->getFile(),
            "line" => $e->getLine()
        ]);
    }
}
