<?php

namespace App\Http\Controllers;

use App\Models\Service;

class ServicesController extends Controller
{
    /**
     * Get all entities
     *
     * Display a listing of the resource.
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        return Service::all();
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
