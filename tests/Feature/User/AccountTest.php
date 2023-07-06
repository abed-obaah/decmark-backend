<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testAccountData()
    {
        $user = $this->loginUser();

        $response = $this->get(route('api.v1.user.account.index'));

        $response->assertStatus(JsonResponse::HTTP_OK)
            ->assertJsonStructure([
                'name',
                'phone',
                'email',
                'status',
                'created_at',
                'wallet' => [
                    'label',
                    'amount' => [
                        'amount',
                        'display',
                        'symbol',
                        'currency'
                    ],
                    'status'
                ]
            ]);
    }
}
