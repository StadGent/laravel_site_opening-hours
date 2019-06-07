<?php

/* @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Openinghours::class, function (Faker\Generator $faker) {
    $start = new Carbon\Carbon('2017-01-01');
    $end = $start->copy()->modify('31 December');
    return [
        'active' => 1,
        'start_date' => $start->subYear(),
        'end_date' => $end->addYear(),
        'label' => $faker->text($maxNbChars = 30),
    ];
});
