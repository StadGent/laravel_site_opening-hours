<?php

/* @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Channel::class, function (Faker\Generator $faker) {
    return [
        'label' => $faker->text($maxNbChars = 30),
        'service_id' => 1,
    ];
});
