<?php

/* @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Calendar::class, function (Faker\Generator $faker) {
    return [
        'priority' => 0,
        'summary' => '',
        'label' => 'Normale uren',
        'closinghours' => 0,
    ];
});
