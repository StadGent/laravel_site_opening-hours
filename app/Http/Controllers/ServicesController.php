<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\User;
use App\Repositories\ServicesRepository;
use Illuminate\Http\Request;

class ServicesController extends Controller
{
    /**
     * @param ServicesRepository $services
     */
    public function __construct(ServicesRepository $services)
    {
        $this->services = $services;
    }

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
     * Get the create form
     *
     * Base not implemnted reply
     *
     * @return \Illuminate\Http\Response 501
     */
    public function create()
    {
        return response()->json('Not Implemented', 501);
    }

    /**
     * Post new entity to store
     *
     * Base not implemnted reply
     *
     * @return \Illuminate\Http\Response 501
     */
    public function store()
    {
        return response()->json('Not Implemented', 501);
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

    /**
     * Get edit form
     *
     * Base not implemnted reply
     *
     * @return \Illuminate\Http\Response 501
     */
    public function edit()
    {
        return response()->json('Not Implemented', 501);
    }

    /**
     * Update/Patch the specified resource in storage.
     *
     * @return \Illuminate\Http\Response 501
     */
    public function update()
    {
        return response()->json('Not Implemented', 501);
    }

    /**
     * Remove entity
     *
     * Base not implemnted reply
     *
     * @return \Illuminate\Http\Response 501
     */
    public function destroy()
    {
        return response()->json('Not Implemented', 501);
    }

}
