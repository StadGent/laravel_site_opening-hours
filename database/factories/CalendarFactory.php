<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CalendarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'priority' => 0,
            'summary' => '',
            'label' => 'Normale uren',
            'closinghours' => 0,
        ];
    }
}
