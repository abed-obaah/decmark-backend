<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Admin;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function testLogin()
    {
        $this->setUpPassport();
        $admin = Admin::factory()->create();

        $response = $this->postJson(route('api.v1.admin.auth.login'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        //$response->dump();

        $response->assertStatus(200)
                ->assertJsonPath('message', 'Login Successful')
                ->assertJsonStructure([
                    'message',
                    'authentication' => [
                        'type',
                        'token',
                    ],
                ]);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testLoginGuest()
    {
        $this->loginAdmin();

        $response = $this->postJson(route('api.v1.admin.auth.login'));
        $response->assertStatus(401);
    }
}
