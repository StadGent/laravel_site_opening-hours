<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class ServicesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json([
            [
                'label' => 'Dienst administratieve vereenvoudiging',
                'uri' => 'http://stad.gent/vereenvoudiging',
                'channels' => [
                    [
                        'id' => 1,
                        'label' => 'telefonisch',
                        'openinghours' => [
                            [
                                'active' => true,
                                'label' => 'vroeger'
                                'start_date' => (Carbon::now())->subYear()->toDateString(),
                                'end_date' => (Carbon::now())->subDay()->toDateString(),
                                'id' => 5
                            ],
                            [
                                'active' => true,
                                'label' => 'toekomst'
                                'start_date' => (Carbon::now())->toDateString(),
                                'end_date' => (Carbon::now())->subDay()->addYear()->toDateString(),
                                'id' => 6
                            ]
                        ]
                    ],
                    [
                        'id' => 2,
                        'label' => 'loket',
                        'openinghours' => [
                            [
                                'active' => true,
                                'label' => 'v2016'
                                'start_date' => '2016-01-01',
                                'end_date' => '2016-12-31',
                                'id' => 5
                            ],
                            [
                                'active' => true,
                                'label' => 'v2017'
                                'start_date' => '2017-01-01',
                                'end_date' => '2017-12-31',
                                'id' => 6
                            ]
                        ]
                    ]
                ]
            ],
            [
                'label' => 'Bib Zuid',
                'uri' => 'http://stad.gent/bib-zuid',
                'channels' => [
                    [
                        'id' => 5,
                        'label' => 'loket',
                        'openinghours' => [
                        ]
                    ]
                ]
            ],
            [
                'label' => 'Bib Sint-Amandsberg',
                'uri' => 'http://stad.gent/bib-sint-amandsberg',
                'channels' => [
                    [
                        'id' => 9,
                        'label' => 'loket',
                        'openinghours' => [
                        ]
                    ]
                ]
            ],
            [
                'label' => 'Bib Ledeberg',
                'uri' => 'http://stad.gent/bib-ledeberg',
                'channels' => [
                    [
                        'id' => 13,
                        'label' => 'loket',
                        'openinghours' => [
                        ]
                    ]
                ]
            ]
        ]);
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
        throw new Exception('Not yet implemented');
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
