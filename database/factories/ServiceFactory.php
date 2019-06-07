<?php

/* @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Service::class, function (Faker\Generator $faker) {
    return [
        'label' => $faker->text,
        'uri' => $faker->url,
        'description' => $faker->text,
        'draft' => 0,
    ];
});
