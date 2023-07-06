<?php

namespace Tests;

use App\Listeners\CreateUserWallet;
use Laravel\Passport\Client;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Auth\Events\Registered;
use Laravel\Passport\Passport;

trait SetUpAuthentication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function setUpPassport()
    {
        $client = Client::factory()->create([
            'personal_access_client' => true
        ]);
        config([
            'passport.personal_access_client.id' => $client->id,
            'passport.personal_access_client.secret' => $client->secret,
        ]);
    }

    public function loginUser(User $user = null)
    {
        Passport::actingAs(
            $user = $user ?? $this->newUser(),
            ['*']
        );

        return $user;
    }

    /**
     * Login test user with an amount
     *
     * @param integer $amount
     * @param User|null $user
     * @return User
     */
    public function loginUserWithBalance($amount = 10000000, User $user = null): User
    {
        $user = $this->loginUser($user);
        $wallet = $user->wallets()->first();
        $wallet->credit($amount, 'Test credit');

        return $user;
    }

    public function newUser(int $count = 1)
    {
        if ($count === 1) {
            $user = User::factory()->create();
            app(CreateUserWallet::class)->handle(new Registered($user));
            return $user;
        } else {
            return User::factory()->count($count)->create()->each(function ($user) {
                app(CreateUserWallet::class)->handle(new Registered($user));
            });
        }
    }

    public function loginAdmin()
    {
        Passport::actingAs(
            $admin = $this->newAdmin(),
            ['*'],
            'api-admin'
        );

        return $admin;
    }

    public function newAdmin(int $count = 1)
    {
        if ($count === 1) {
            return Admin::factory()->create();
        } else {
            return Admin::factory()->count($count)->create();
        }
    }
}
