<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServicesController extends Controller
{
    /**
     * Get all entities
     *
     * Display a listing of the resource.
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function index(Request $request)
    {
        return Service::where('label', 'like', '%' . $request->get('label', '') . '%')
            ->where('uri', 'like', '%' . $request->get('uri', '') . '%')->get();
    }

    /**
     * Get with id
     *
     * Base get and return the service
     *
     * @return \App\Models\Service
     */
    public function show(Service $service)
    {
        return $service;
    }
}
