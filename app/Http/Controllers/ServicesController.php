<?php

namespace App\Http\Controllers;

use App\Http\Transformers\ServiceTransformer;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

        $services = Service::where('label', 'like', '%' . $label . '%');

        if (!empty($uri)) {
            $services->where('uri', $uri);
        }

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
        return response()->item(new ServiceTransformer(), $service);
    }
}
