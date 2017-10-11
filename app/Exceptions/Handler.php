<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * in case of expectsJson => handle into correct microsoft api error-object
     * https://github.com/Microsoft/api-guidelines/blob/master/Guidelines.md#7102-error-condition-responses
     * http://docs.oasis-open.org/odata/odata-json-format/v4.0/os/odata-json-format-v4.0-os.html#_Toc372793091
     * @todo logId should be created for reference
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($request->expectsJson()) {
            /** */
            $errorObj = new \stdClass();
            $errorObj->code = "";
            $errorObj->message = "";
            $errorObj->target = "";
            /*******************************/
            /*          Optional           */
            /*******************************/
            /* $errorObj->details = [];    */
            /* $errorObj->innererror = []; */
            /*******************************/

            if ($exception instanceof NotFoundHttpException) {
                $errorObj->code = "PathNotFound";
                $errorObj->message = "The requested path could not match a route in the API";
                $errorObj->target = "query";

                return response()->json(['error' => $errorObj], 404);
            }

            // https://restpatterns.mindtouch.us/HTTP_Status_Codes/422_-_Unprocessable_Entity
            if ($exception instanceof ModelNotFoundException) {
                $fullmodel = $exception->getModel();
                $choppedUpModel = explode('\\', $fullmodel);
                $cleanedUpModel = array_pop($choppedUpModel);

                $errorObj->code = "ModelNotFound";
                $errorObj->message = $cleanedUpModel . " model is not found with given identifier";
                $errorObj->target = $cleanedUpModel;

                return response()->json(['error' => $errorObj], 422);
            }
            // https://github.com/Microsoft/api-guidelines/blob/master/Guidelines.md#examples
            if ($exception instanceof ValidationException) {
                $errorObj->code = "NotValidParameter";
                $errorObj->message = "Paramters did not pass validation";
                $errorObj->target = "parameters";
                $errorObj->details = [];

                foreach ($exception->validator->errors()->getMessages() as $field => $message) {
                    $details = new \stdClass();
                    $details->code = "NotValidParameter";
                    $details->message = $message[0];
                    $details->target = $field;
                    if ($field === 'Channel') {
                        $details->code = "ParentChildMismatch";
                        $details->message = "The requested service did not find a match for the given channel identifier";
                    }
                    $errorObj->details[] = $details;
                }

                return response()->json(['error' => $errorObj], 400);
            }
        }

        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest('login');
    }
}
