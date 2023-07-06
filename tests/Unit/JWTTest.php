<?php

namespace Tests\Unit;

use App\Rules\JWT;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class JWTTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testClusure()
    {
        $data = [
            'user_id' => 'fgkdfbkjsnbksjdfn',
            'code' => '123456',
            'action' => 'reset_password',
            'expires_at' => now()->addMinutes(10)->getTimestamp()
        ];

        $token = Crypt::encryptString(json_encode($data));

        $jwt = JWT::check(function ($decrypted) use ($data) {
            $this->assertTrue($cond1 = $decrypted['user_id'] === $data['user_id']);
            $this->assertTrue($cond2 = $decrypted['code'] === $data['code']);
            $this->assertTrue($cond3 = $decrypted['action'] === $data['action']);
            $this->assertTrue($cond4 = $decrypted['expires_at'] === $data['expires_at']);

            return $cond1 && $cond2 && $cond3 && $cond4;
        });

        $this->assertTrue($jwt->validate($token));
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testPassesMethod()
    {
        $data = [
            'user_id' => 'fgkdfbkjsnbksjdfn',
            'code' => '123456',
            'action' => 'reset_password',
            'expires_at' => now()->addMinutes(10)->getTimestamp()
        ];

        $token = Crypt::encryptString(json_encode($data));

        $jwt = JWT::check(function ($decrypted) use ($data) {
            $this->assertTrue($cond1 = $decrypted['user_id'] === $data['user_id']);
            $this->assertTrue($cond2 = $decrypted['code'] === $data['code']);
            $this->assertTrue($cond3 = $decrypted['action'] === $data['action']);
            $this->assertTrue($cond4 = $decrypted['expires_at'] === $data['expires_at']);

            return $cond1 && $cond2 && $cond3 && $cond4;
        });

        $this->assertTrue($jwt->passes('token', $token));
        $this->assertTrue(App::make('request')->has('tokenDecrypted'));
    }
}
