<?php

namespace App\Http\Controllers\UI;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use App\Repositories\ServicesRepository;
use Illuminate\Http\Request;

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
        $this->middleware('admin')->except('index');
        $this->servicesRepository = $servicesRepository;
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
        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', null);

        if ($request->user('api')->hasRole('Admin')) {
            return $this->servicesRepository->getExpandedServices(null, $offset, $limit);
        }

        return $this->servicesRepository->getExpandedServiceForUser($request->user('api')->id);
    }

    /**
     * Update/Patch the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\Service $service
     * @return Collection
     */
    public function update(Request $request, Service $service)
    {
        // The only field we allow to be updated is the draft flag
        $draft = $request->input('draft', null);

        if (!is_null($draft)) {
            $service->draft = $draft;
            $service->save();
        }
        $expandedServices = $this->servicesRepository->getExpandedServices($service->id);

        return response()->json($expandedServices);
    }
}
