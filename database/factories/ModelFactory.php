<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
 */

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\SUser::class, function (Faker\Generator $faker) {
    /**
     * @var string
     */
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
    ];
});

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Service::class, function (Faker\Generator $faker) {
    return [
        'label' => $faker->text,
        'uri' => $faker->url,
        'description' => $faker->text,
        'draft' => 0,
    ];
});

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Channel::class, function (Faker\Generator $faker) {
    return [
        'label' => $faker->text($maxNbChars = 30),
        'service_id' => 1,
    ];
});
