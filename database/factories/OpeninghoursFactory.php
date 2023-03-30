<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OpeninghoursFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $start = new Carbon\Carbon('2017-01-01');
        $end = $start->copy()->modify('31 December');
        return [
            'active' => 1,
            'start_date' => $start->subYear(),
            'end_date' => $end->addYear(),
            'label' => $faker->text($maxNbChars = 30),
        ];
    }
}
