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
        $this->middleware('auth:api')->except(['index', 'show']);
    }

    /**
     * Get all entities
     *
     * Display a listing of the resource.
     *
     * @return App\Models\Service
     */
    public function index(Request $request)
    {
        if ($request->user('api') && !$request->user('api')->hasRole('Admin')) {
            return $request->user('api')->services()->get();
        }

        return Service::all();
    }

    /**
     * Get the create form
     *
     * Base not implemnted reply
     *
     * @return \Illuminate\Http\Response 501
     */
    public function create(Service $request)
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
    public function store(Service $request)
    {
        return response()->json('Not Implemented', 501);
    }

    /**
     * Get with id
     *
     * Base get and return the service
     *
     * @return Service
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
    public function edit(Service $request)
    {
        return response()->json('Not Implemented', 501);
    }

    /**
     * Update/Patch the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        /** should be handled by routes... but just to be sure **/
        if (!$request->user('api')) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        /** only allowed for admin user **/
        if (!$request->user('api')->hasRole('Admin')) {
            return response()->json(['message' => 'Method not allowed'], 405);
        }

        // The only field we allow to be updated is the draft flag
        $draft = $request->input('draft', null);

        if (!is_null($draft)) {
            $this->services->update($id, ['draft' => $draft]);
        }

        return response()->json($this->services->getById($id));
    }

    /**
     * Remove entity
     *
     * Base not implemnted reply
     *
     * @return \Illuminate\Http\Response 501
     */
    public function destroy(Service $request)
    {
        return response()->json('Not Implemented', 501);
    }

}
