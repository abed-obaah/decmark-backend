<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivationCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'token' => Str::random(6),
            'action' => 'activate',
            'expires_at' => now()->addMinutes(60)
        ];
    }

    /**
     * Indicate that the user is active.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function owner(Model $owner)
    {
        return $this->state(function (array $attributes) use ($owner) {
            return [
                'owner_id' => $owner->getKey(),
                'owner_type' => $owner->getMorphClass(),
            ];
        });
    }

    /**
     * Indicate that the user is active.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function addFor(Model $for)
    {
        return $this->state(function (array $attributes) use ($for) {
            return [
                'for_id' => $for->getKey(),
                'for_type' => $for->getMorphClass(),
            ];
        });
    }
}
