<?php

namespace Database\Factories;

use App\Http\Controllers\Api\One\User\Auth\RegisterController;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'tag' => RegisterController::generateUserTag($this->faker->firstName, $this->faker->lastName),
            'fb_id' => $this->faker->uuid(),
            'email' => Str::random(4) . '.' . $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'phone' => $this->faker->numerify('23480########'),
            'phone_verified_at' => now(),
            'password' => 'password'
        ];
    }

    /**
     * Indicate that the user is active.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverifiedPhone()
    {
        return $this->state(function (array $attributes) {
            return [
                'phone_verified_at' => null,
            ];
        });
    }

    /**
     * Indicate that the user is active.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverifiedEmail()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}
