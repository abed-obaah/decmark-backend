<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CourierTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateCourier()
    {
        $this->loginUser();

        $response = $this->post(route('api.v1.user.courier.create', [
            "title" => "Sendbox courier",
            "origin" => [
                10.5060,
                7.3679
            ],
            "destination" => [
                10.5060,
                7.3679
            ],
            "description" => "Lorem ipsum dolor sit amet",
            "price" => 8000
        ]));

        $response->assertStatus(200)->assertJsonStructure([
            'id',
            'title',
            'description',
            'origin',
            'destination',
            'artisan',
            'user',
            'status',
            'created_at',
        ]);
    }
}
