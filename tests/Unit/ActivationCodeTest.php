<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\ActivationCode;
use App\Models\User;
use App\Rules\Activation;
use Tests\TestCase;

class ActivationCodeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testHashing()
    {
        $expected = \hash('sha256', '12345');

        $this->assertTrue(hash_equals($expected, ActivationCode::hash('12345')));
    }

    public function testValidation()
    {
        $user = User::factory()->create();
        ActivationCode::factory()->owner($user)->create([
            'action' => 'reset_password',
            'token' => ActivationCode::hash($otp = '123456')
        ]);

        $activation = Activation::start()->action('reset_password')->owner($user);

        $this->assertTrue($activation->passes('otp', $otp));
    }

    public function testValidationInvalidAction()
    {
        $user = User::factory()->create();
        $code = ActivationCode::factory()->owner($user)->create([
            'action' => 'reset_password',
            'token' => ActivationCode::hash($otp = '123456')
        ]);

        $activation = Activation::start()->action('reset')->owner($user);

        $this->assertNotTrue($activation->passes('otp', $otp));
    }

    public function testValidationExpiry()
    {
        $user = User::factory()->create();
        $code = ActivationCode::factory()->owner($user)->create([
            'action' => 'reset_password',
            'token' => ActivationCode::hash($otp = '123456'),
            'expires_at' => now()->addMinutes(10)
        ]);

        $this->travel(11)->minutes();

        $activation = Activation::start()->action('reset_password')->owner($user);

        $this->assertNotTrue($activation->passes('otp', $otp));
    }

    public function testValidationWrongCode()
    {
        $user = User::factory()->create();
        $code = ActivationCode::factory()->owner($user)->create([
            'action' => 'reset_password',
            'token' => ActivationCode::hash($otp = '123456'),
            'expires_at' => now()->addMinutes(10)
        ]);

        $this->travel(11)->minutes();

        $activation = Activation::start()->action('reset_password')->owner($user);

        $this->assertNotTrue($activation->passes('otp', '1232'));
    }
}
