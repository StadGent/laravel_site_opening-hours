<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Service;

class ChannelController extends Controller
{
    /**
     * Get all entities
     *
     * Will not be allowed as this is WAY TOO MUCH data
     * the channels need to be required by the getFromService method
     * Base not implemnted reply
     *
     * @return \Illuminate\Http\Response 501
     */
    public function index()
    {
        return response()->json('Not Implemented', 501);
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
     * Base get and return the Channel
     *
     * @return \App\Models\Channel
     */
    public function show(Channel $channel)
    {
        return $channel;
    }

    /**
     * Get Subset of channels from Service
     *
     * @param Service $service
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function getFromService(Service $service)
    {
        return $service->channels;
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
     * Base not implemnted reply
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
