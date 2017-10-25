<?php

use Carbon\Carbon;

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

/* @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\User::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => bcrypt('secret'),
        'remember_token' => str_random(10),
        'token' => str_random(10),
    ];
});

/* @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Service::class, function (Faker\Generator $faker) {
    return [
        'label' => $faker->text,
        'uri' => $faker->url,
        'description' => $faker->text,
        'draft' => 0,
    ];
});

/* @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Channel::class, function (Faker\Generator $faker) {
    return [
        'label' => $faker->text($maxNbChars = 30),
        'service_id' => 1,
    ];
});

/* @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Openinghours::class, function (Faker\Generator $faker) {
    $start = new Carbon('2017-01-01');
    $end = $start->copy()->modify('31 December');

    return [
        'active' => 1,
        'start_date' => $start->subYear(),
        'end_date' => $end->addYear(),
        'label' => $faker->text($maxNbChars = 30),
    ];
});

/* @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Calendar::class, function (Faker\Generator $faker) {
    return [
        'priority' => 0,
        'summary' => '',
        'label' => 'Normale uren',
        'closinghours' => 0,
    ];
});

/* @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\Models\Event::class, function (Faker\Generator $faker) {
    $dt = new Carbon('2017-01-01');
    $dt->minute = 00;
    $dt->second = 00;

    $start = $dt->copy();
    $start->hour = 9;
    $end = $dt->copy();
    $end->hour = 18;

    return [
        'rrule' => 'BYDAY=MO,TU,WE,TH,FR;FREQ=WEEKLY',
        'start_date' => $start,
        'end_date' => $end,
        'label' => 1,
        'until' => $dt->modify('31 December')->addYear(),
    ];
});
