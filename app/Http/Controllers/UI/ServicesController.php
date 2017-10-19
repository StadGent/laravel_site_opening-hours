<?php

namespace App\Http\Controllers\UI;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use App\Repositories\ServicesRepository;
use Illuminate\Http\Request;
use UnexpectedValueException;

/**
 * Controller for the UI with extended models
 */
class ServicesController extends Controller
{
    /**
     * @var ServicesRepository
     */
    protected $servicesRepository;

    /**
     * @param ServicesRepository $services
     */
    public function __construct(ServicesRepository $servicesRepository)
    {
        $this->servicesRepository = $servicesRepository;
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
            return $this->servicesRepository->getExpandedServices();
        }

        return $this->servicesRepository->getExpandedServiceForUser($request->user('api')->id);
    }

    /**
     * Get the create form not allowed
     *
     * @throws UnexpectedValueException
     */
    public function create()
    {
        throw new UnexpectedValueException();
    }

    /**
     * Post new entity to store not allowed
     *
     * @throws UnexpectedValueException
     */
    public function store()
    {
        throw new UnexpectedValueException();
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
     * Get edit form not allowed
     *
     * @throws UnexpectedValueException
     */
    public function edit()
    {
        throw new UnexpectedValueException();
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
        // should be handled by routes... but just to be sure
        if (!$request->user('api')) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        // only allowed for admin user
        if (!$request->user('api')->hasRole('Admin')) {
            return response()->json(['message' => 'Method not allowed'], 405);
        }

        // The only field we allow to be updated is the draft flag
        $draft = $request->input('draft', null);

        if (!is_null($draft)) {
            $service->draft = $draft;
            $service->save();
        }
        $expandedServices = $this->servicesRepository->getExpandedServices($service->id);

        return response()->json($expandedServices);
    }

    /**
     * Destroy entity not allowed
     *
     * @throws UnexpectedValueException
     */
    public function destroy()
    {
        throw new UnexpectedValueException();
    }
}
