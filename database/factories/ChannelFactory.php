<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ChannelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'label' => $this->faker->text(30),
            'service_id' => 1,
        ];
    }
}
