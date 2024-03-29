<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * @var stdClass
     */
    private $errorObj;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * setup basic error Obj with main properties
     *
     * Optional properties that can be added:
     * - details = [];
     * - innererror = [];
     *
     * @return stdClass
     */
    private function initErrorObj()
    {
        $this->errorObj = new \stdClass();
        $this->errorObj->code = "";
        $this->errorObj->message = "";
        $this->errorObj->target = "";
    }

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * In case json is involved the output is set on json
     * init correct api error-object (by Microsoft API guidelines)
     * https://github.com/Microsoft/api-guidelines/blob/master/Guidelines.md#7102-error-condition-responses
     * http://docs.oasis-open.org/odata/odata-json-format/v4.0/os/odata-json-format-v4.0-os.html#_Toc372793091
     * search dynamic for handler method of exception type
     *
     * @param  Request  $request
     * @param  \Throwable  $exception
     * @return Response
     */
    public function render($request, Throwable $exception)
    {
        $this->initErrorObj();
        $reflect = new \ReflectionClass($exception);
        $method = 'handle' . $reflect->getShortName();
        if (method_exists($this, $method) && ($request->isJson() || $request->expectsJson() || $method === 'unauthenticated')) {
            $this->errorObj->code = $reflect->getShortName();

            return $this->$method($exception, $request)->header('Access-Control-Allow-Origin', '*');
        }

        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  Request  $request
     * @param  AuthenticationException  $exception
     * @return Response 401
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->isJson() || $request->expectsJson()) {
            $this->errorObj = new \stdClass();
            $this->errorObj->code = "AuthenticationException";
            $this->errorObj->message = $exception->getMessage();
            $this->errorObj->message .= "You are not authorised to make this request";

            $this->errorObj->target = "query";

            return response()->json(['error' => $this->errorObj], 401);
        }

        return redirect()->guest(route('login'));
    }

    /**
     * NotFoundHttpException
     * The requested path could not match a route in the API
     *
     * @param NotFoundHttpException $exception
     * @return Response 404
     */
    protected function handleNotFoundHttpException(NotFoundHttpException $exception)
    {
        $this->errorObj->message = "The requested path could not match a route in the API";
        $this->errorObj->target = "query";

        return response()->json(['error' => $this->errorObj], 404);
    }

    /**
     * MethodNotAllowedHttpException
     * The used HTTP method is not allowed on this route in the API
     *
     * @param MethodNotAllowedHttpException $exception
     * @return Response 405
     */
    protected function handleMethodNotAllowedHttpException(MethodNotAllowedHttpException $exception)
    {
        $this->errorObj->message = "The used HTTP method is not allowed on this route in the API";
        $this->errorObj->target = "query";

        return response()->json(['error' => $this->errorObj], 405);
    }

    /**
     * MethodNotAllowedHttpException
     * The used HTTP Accept header is not allowed on this route in the API
     *
     * @param NotAcceptableHttpException $exception
     * @return Response 406
     */
    protected function handleNotAcceptableHttpException(NotAcceptableHttpException $exception)
    {
        $this->errorObj->message = "The used HTTP Accept header is not allowed on this route in the API";
        $this->errorObj->target = "query";

        return response()->json(['error' => $this->errorObj], 406);
    }

    /**
     * ModelNotFoundException
     * The model is not found with given identifier
     * https://restpatterns.mindtouch.us/HTTP_Status_Codes/422_-_Unprocessable_Entity
     *
     * @param ModelNotFoundException $exception
     * @return Response 422
     */
    protected function handleModelNotFoundException(ModelNotFoundException $exception)
    {
        $fullmodel = $exception->getModel();
        $choppedUpModel = explode('\\', $fullmodel);
        $cleanedUpModel = array_pop($choppedUpModel);
        $this->errorObj->message = $cleanedUpModel . " model is not found with given identifier";
        $this->errorObj->target = $cleanedUpModel;

        return response()->json(['error' => $this->errorObj], 422);
    }

    /**
     * ValidationException
     * Parameters did not pass validatio
     * https://github.com/Microsoft/api-guidelines/blob/master/Guidelines.md#examples
     *
     * @param ValidationException $exception
     * @return Response 400
     */
    protected function handleValidationException(ValidationException $exception)
    {
        $this->errorObj->message = "Parameters did not pass validation";
        $this->errorObj->target = "parameters";
        $this->errorObj->details = [];

        foreach ($exception->validator->errors()->getMessages() as $field => $message) {
            $details = new \stdClass();
            $details->code = "NotValidParameter";
            $details->message = $message[0];
            $details->target = $field;
            if ($field === 'Channel') {
                $details->code = "ParentChildMismatch";
                $details->message = "The requested service did not find a match for the given channel identifier";
            }
            $this->errorObj->details[] = $details;
        }

        return response()->json(['error' => $this->errorObj], 400);
    }
}
