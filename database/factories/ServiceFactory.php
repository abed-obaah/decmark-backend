<?php

namespace Database\Factories;

use App\Enums\ServiceStatusEnum;
use App\Enums\ServiceTypeEnum;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'coordinate' => config('database.default') !== 'sqlite' ?
                new Point(7.615736, 5.235415) : 'point(7.615736, 5.235415)',
            'title' => 'This is a service',
            'type' => ServiceTypeEnum::TAYLORING,
            'price' => 1000000,
            'description' => 'This is a service created durring test and it is very cheap!!!',
            'duration' => 10,
            'status' => ServiceStatusEnum::PENDING,
        ];
    }

    /**
     * User that created it
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function user(Model $user)
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => $user->getKey(),
            ];
        });
    }
}
