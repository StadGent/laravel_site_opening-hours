<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ServicesRepository;

class ServicesController extends Controller
{
    public function __construct(ServicesRepository $services)
    {
        $this->middleware('auth');

        $this->services = $services;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // An admin has access to all of the roles
        if ($request->user()->hasRole('Admin')) {
            return response()->json($this->services->get());
        }

        return response()->json($this->services->getForUser($request->user()->id));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        throw new Exception('Not yet implemented');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        throw new Exception('Not yet implemented');
    }

    /**
     * Display the specified resource.
     *
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        throw new Exception('Not yet implemented');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        throw new Exception('Not yet implemented');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // The only field we allow to be updated is the draft flag
        $draft = $request->input('draft', null);

        if (! is_null($draft)) {
            $this->services->update($id, ['draft' => $draft]);
        }

        return response()->json($this->services->getById($id));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        throw new Exception('Not yet implemented');
    }
}
