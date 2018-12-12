<?php

namespace App\Http\Controllers;

use App\Models\Type;
use Illuminate\Http\Request;

class TypeController extends Controller
{
    /**
     * Get all entities
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return response(Type::all());
    }
}
