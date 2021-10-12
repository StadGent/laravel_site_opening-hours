<?php

namespace App\Http\Controllers;

use App\Http\Transformers\ServiceTransformer;
use App\Models\Service;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServicesController extends Controller
{
    /**
     * Get all entities
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $label = $request->get('label', '');
        $uri = $request->get('uri', '');
        $source = $request->get('source', '');
        $sourceId = $request->get('sourceId', '');

        $services = Service::where('label', 'like', '%' . $label . '%');

        if (!empty($uri)) {
            $services->where('uri', $uri);
        }
        if (!empty($source)) {
            $services->where('source', $source);
        }
        if (!empty($sourceId)) {
            $services->where('identifier', $sourceId);
        }


        $services->where('draft', false);

        return response()->collection(new ServiceTransformer(), $services->get());
    }

    /**
     * Get with id
     * Base get and return the service
     *
     * @return Response
     */
    public function show(Service $service)
    {
        if($service->draft){
            $exception = new ModelNotFoundException();
            $exception->setModel(Service::class);
            throw $exception;
        }

        return response()->item(new ServiceTransformer(), $service);
    }
}
