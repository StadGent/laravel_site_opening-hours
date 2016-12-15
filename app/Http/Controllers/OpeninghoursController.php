<?php

namespace App\Http\Controllers;

use App\Repositories\OpeninghoursRepository;
use App\Http\Requests\StoreOpeninghoursRequest;
use App\Http\Requests\DeleteOpeninghoursRequest;

class OpeninghoursController extends Controller
{
    public function __construct(OpeninghoursRepository $openinghours)
    {
        $this->openinghours = $openinghours;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        throw new Exception('Not yet implemented');
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
    public function store(StoreOpeninghoursRequest $request)
    {
        $input = $request->input();

        $id = $this->openinghours->store($input);

        $openinghours = $this->openinghours->getById($id);

        if (! empty($openinghours)) {
            return response()->json($openinghours);
        }

        return response()->json(['message' => 'Something went wrong while storing the new openingshours, check the logs.'], 400);
    }

    /**
     * Display the specified resource.
     *
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return response()->json($this->openinghours->getById($id));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function update(StoreOpeninghoursRequest $request, $id)
    {
        $input = $request->input();

        $success = $this->openinghours->update($id, $input);

        if ($success) {
            return response()->json($this->openinghours->getById($id));
        }

        return response()->json(['message' => 'Something went wrong while updating the openinghours, check the logs.'], 400);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeleteOpeninghoursRequest $id)
    {
        $success = $this->openinghours->delete($id);

        if ($success) {
            return response()->json(['message' => 'De openingsuren werden verwijderd']);
        }

        return reponse()->json(['message' => 'De openingsuren werden niet verwijderd, er is iets foutgegaan.'], 400);
    }
}
