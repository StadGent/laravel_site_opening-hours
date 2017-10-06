<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\User;
use App\Repositories\ServicesRepository;
use Illuminate\Http\Request;

/**
 * Controller for the UI with extended models
 */
class ServicesUiController extends Controller
{
    /**
     * @param ServicesRepository $services
     */
    public function __construct(ServicesRepository $services)
    {
        $this->services = $services;
        $this->middleware('auth:api');
    }

    /**
     * Get all entities
     *
     * Display an extended listing of the resource for the ui.
     *
     * @return App\Models\Service
     */
    public function index(Request $request)
    {
        if ($request->user('api')->hasRole('Admin')) {
            return app('ServicesRepository')->getExpandedServices();
        }

        return app('ServicesRepository')->getExpandedServiceForUser($request->user('api')->id);
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
     * @param  \App\Models\Service       $service
     * @return Collection
     */
    public function update(Request $request, Service $service)
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
            $service->draft = $draft;
            $service->save();
        }

        return app('ServicesRepository')->getExpandedServices($service->id);
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
