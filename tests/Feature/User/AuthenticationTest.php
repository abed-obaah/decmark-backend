<?php

namespace Tests\Feature\User;

use App\Enums\SudoTypeEnum;
use App\Listeners\SendUserVerification;
use App\Mail\User\ResetPasswordMail;
use App\Mail\User\VerifyEmailMail;
use App\Models\ActivationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use ManeOlawale\Laravel\Termii\Facades\Termii;
use ManeOlawale\Laravel\Termii\Testing\Sequence;
use Spatie\TestTime\TestTime;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testRegistrationGuest()
    {
        $this->loginUser();
        $response = $this->put(route('api.v1.user.auth.register'));
        //$response->dump();
        $response->assertStatus(401);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testRegistration()
    {
        Event::fake([
            Registered::class,
        ]);
        $this->setUpPassport();

        $response = $this->putJson(route('api.v1.user.auth.register'), $data = [
            'first_name' => 'Olawale',
            'last_name' => 'Ilesanmi',
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->numerify('23480########'),
            'password' => 'whatever',
            'password_confirmation' => 'whatever',
            'accept_terms' => true
        ]);

        $response->assertStatus(JsonResponse::HTTP_CREATED)
            ->assertJsonPath('message', 'Registration successful.');

        $user = new User();

        Event::assertDispatched(function (Registered $event) use ($data, &$user) {
            $user = $event->user;

            return $event->user->first_name === $data['first_name'] && $event->user->last_name === $data['last_name'] &&
                $event->user->phone === $data['phone'] && $event->user->email === $data['email'] &&
                Hash::check($data['password'], $event->user->password);
        });
        Event::assertListening(
            Registered::class,
            SendUserVerification::class
        );
    }

    public function testLogin()
    {
        $this->setUpPassport();
        $user = User::factory()->create();

        $response = $this->postJson(route('api.v1.user.auth.login'), [
            'handle' => $user->email,
            'password' => 'password',
        ]);

        //$response->dump();

        $response->assertStatus(JsonResponse::HTTP_OK)
                ->assertJsonPath('message', 'Login Successful.')
                ->assertJsonStructure([
                    'message',
                    'authentication' => [
                        'type',
                        'token',
                    ],
                ]);
    }

    public function testWrongPassword()
    {
        $this->setUpPassport();
        $user = User::factory()->create();

        $response = $this->postJson(route('api.v1.user.auth.login'), [
            'handle' => $user->email,
            'password' => 'passwor342',
        ]);

        //$response->dump();

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonStructure([
                    'message',
                    'errors' => [
                        'email',
                    ],
                ]);
    }

    public function testLoginWithPhone()
    {
        $this->setUpPassport();
        $user = User::factory()->create();

        $response = $this->postJson(route('api.v1.user.auth.login'), [
            'handle' => $user->phone,
            'password' => 'password',
        ]);

        $response->assertStatus(JsonResponse::HTTP_OK)
                ->assertJsonPath('message', 'Login Successful.')
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
        $this->loginUser();

        $response = $this->postJson(route('api.v1.user.auth.login'));

        $response->assertStatus(401);
    }

    public function testChangePassword()
    {
        $user = $this->loginUser();
        $password = $user->password;

        $response = $this->postJson(route('api.v1.user.auth.changePassword'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password'
        ]);

        $response->assertStatus(JsonResponse::HTTP_ACCEPTED)
                ->assertJsonPath(
                    'message',
                    'Password updated successfully.'
                )
                ->assertJsonStructure([
                    'message',
                ]);
        $this->assertNotTrue(Hash::check($password, $user->fresh()->password));
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function testChangePasswordError()
    {
        $user = $this->loginUser();
        $password = $user->password;

        $response = $this->postJson(route('api.v1.user.auth.changePassword'), [
            'current_password' => 'passwor',
            'password' => 'new-password',
            'password_confirmation' => 'new-password'
        ]);

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonPath(
                    'message',
                    'The given data was invalid.'
                )
                ->assertJsonStructure([
                    'message',
                    'errors' => [
                        'current_password'
                    ]
                ]);
        $this->assertTrue($password === $user->fresh()->password);
    }

    public function testRequestResetPassword()
    {
        Mail::fake();
        $user = User::factory()->create();

        $response = $this->postJson(route('api.v1.user.auth.password.request'), [
            'email' => $user->email,
        ]);

        $response->assertStatus(JsonResponse::HTTP_OK)
                ->assertJsonPath(
                    'message',
                    'We have sent an OTP to your email.'
                )
                ->assertJsonStructure([
                    'message',
                ]);
        Mail::assertQueued(ResetPasswordMail::class);
        $this->assertTrue(
            $user->activationCodes()
                ->whereAction('reset_password')->count() === 1
        );
    }

    public function testRequestResetPasswordNotSent()
    {
        Mail::fake();
        $response = $this->postJson(route('api.v1.user.auth.password.request'), [
            'email' => 'olawale@gmail.com',
        ]);

        $response->assertStatus(JsonResponse::HTTP_OK)
                ->assertJsonPath(
                    'message',
                    'We have sent an OTP to your email.'
                )
                ->assertJsonStructure([
                    'message',
                ]);
        Mail::assertNotQueued(ResetPasswordMail::class);
        $this->assertTrue(
            ActivationCode::whereAction('reset_password')->count() === 0
        );
    }

    public function testResetPassword()
    {
        TestTime::freeze();
        $user = User::factory()->create();
        $password = $user->password;

        $data = [
            'user_id' => $user->id,
            'code' => '123456',
            'action' => 'reset_password',
            'expires_at' => now()->addMinutes(10)->getTimestamp()
        ];

        $token = Crypt::encryptString(json_encode($data));

        $response = $this->postJson(route('api.v1.user.auth.password.reset'), [
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password'
        ]);

        $response->assertStatus(JsonResponse::HTTP_OK)
                ->assertJsonPath(
                    'message',
                    'Password reset successful.'
                )
                ->assertJsonStructure([
                    'message',
                ]);
        $this->assertNotTrue(Hash::check($password, $user->fresh()->password));
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function testResetPasswordError()
    {
        TestTime::freeze();
        $user = User::factory()->create();
        $password = $user->password;

        $data = [
            'user_id' => $user->id,
            'token' => '123456',
            'action' => 'reset_password',
            'expires_at' => now()->subMinutes(10)->getTimestamp()
        ];

        $token = Crypt::encryptString(json_encode($data));

        $response = $this->postJson(route('api.v1.user.auth.password.reset'), [
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-passwor'
        ]);

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonPath(
                    'message',
                    'The given data was invalid.'
                )
                ->assertJsonStructure([
                    'message',
                    'errors' => [
                        'token',
                        'password'
                    ]
                ]);
        $this->assertTrue($password === $user->fresh()->password);
    }

    public function testResendVerifyEmailMail()
    {
        Mail::fake();
        $user = $this->loginUser();
        $user->forceFill([
            'email_verified_at' => null
        ])->update();

        $response = $this->postJson(route('api.v1.user.auth.email.resend'));

        $response->assertStatus(JsonResponse::HTTP_ACCEPTED)
                ->assertJsonPath(
                    'message',
                    'Verification email resent.'
                )
                ->assertJsonStructure([
                    'message',
                ]);

        $this->assertTrue($user->activationCodes()->whereAction('verify_email')->count() === 1);
        Mail::assertQueued(VerifyEmailMail::class);
    }

    public function testResendVerifyEmailMailWhenAlreadyVerified()
    {
        Mail::fake();
        $user = $this->loginUser();

        $response = $this->postJson(route('api.v1.user.auth.email.resend'));

        $response->assertStatus(JsonResponse::HTTP_CONFLICT)
                ->assertJsonPath(
                    'message',
                    'Email already verified.'
                )
                ->assertJsonStructure([
                    'message',
                ]);

        $this->assertTrue($user->activationCodes()->whereAction('verify_email')->count() === 0);
        Mail::assertNotQueued(VerifyEmailMail::class);
    }


    public function testVerifyEmailMail()
    {
        $user = $this->loginUser();
        $user->forceFill([
            'email_verified_at' => null
        ])->update();

        $code = ActivationCode::factory()->owner($user)->create([
            'action' => 'verify_email',
            'token' => ActivationCode::hash($token = '123456')
        ]);

        $response = $this->postJson(route('api.v1.user.auth.email.verify'), [
            'code' => $token
        ]);

        $response->assertStatus(JsonResponse::HTTP_ACCEPTED)
                ->assertJsonPath(
                    'message',
                    'Email verified successfully.'
                )
                ->assertJsonStructure([
                    'message',
                ]);

        $this->assertTrue(
            $user->activationCodes()
                ->whereAction($code->action)
                ->whereToken($code->token)->count() === 0
        );
    }

    public function testGoogleAuthLogin()
    {
        $this->setUpPassport();
        Event::fake([
            Registered::class,
        ]);
        $user = User::factory()->create();

        /**
         * @var \Laravel\Socialite\Two\GoogleProvider
         */
        $driver = Socialite::driver('google');

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($data = [
                'azp' => '272196069173.apps.googleusercontent.com',
                'aud' => '272196069173.apps.googleusercontent.com',
                'sub' => '110248495921238986420',
                'hd' => 'decmark.io',
                'email' => $user->email,
                'name' => 'Ilesanmi Olawale',
                'nickname' => 'Olawale',
                'picture' => 'url',
                'email_verified' => true,
                'at_hash' => '0bzSP5g7IfV3HXoLwYS3Lg',
                'exp' => 1524601669,
                'iss' => 'https://accounts.google.com',
                'iat' => 1524598069
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $driver->setHttpClient($client);

        $response = $this->postJson(route('api.v1.user.auth.social', [
            'driver' => 'google'
        ]), [
            'token' => 'sngdsdhvjdvgfsjdfvjshvbsjfvhfsjbvhjfbv '
        ]);

        $response->assertStatus(JsonResponse::HTTP_OK)
            ->assertJsonPath('message', 'Login Successful.')
            ->assertJsonPath('action', 'login')
            ->assertJsonStructure([
                'message',
                'action',
                'authentication' => [
                    'type',
                    'token',
                ],
            ]);
        Event::assertNotDispatched(Registered::class);
    }

    public function testGoogleAuthRegister()
    {
        $this->setUpPassport();
        Event::fake([
            Registered::class,
        ]);

        /**
         * @var \Laravel\Socialite\Two\GoogleProvider
         */
        $driver = Socialite::driver('google');

        $email = 'olawale@gmail.com';

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($data = [
                'azp' => '272196069173.apps.googleusercontent.com',
                'aud' => '272196069173.apps.googleusercontent.com',
                'sub' => '110248495921238986420',
                'hd' => 'decmark.io',
                'email' => $email,
                'name' => 'Olawale Ilesanmi',
                'nickname' => 'Olawale',
                'picture' => 'url',
                'email_verified' => true,
                'at_hash' => '0bzSP5g7IfV3HXoLwYS3Lg',
                'exp' => 1524601669,
                'iss' => 'https://accounts.google.com',
                'iat' => 1524598069
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $driver->setHttpClient($client);
        $firstName = explode(' ', $data['name'])[0];

        $response = $this->postJson(route('api.v1.user.auth.social', [
            'driver' => 'google'
        ]), [
            'token' => 'sngdsdhvjdvgfsjdfvjshvbsjfvhfsjbvhjfbv '
        ]);

        $this->assertSame(User::whereEmail($email)->count(), 1);

        $response->assertStatus(JsonResponse::HTTP_CREATED)
            ->assertJsonPath('message', 'Registration successful.')
            ->assertJsonPath('action', 'registration')
            ->assertJsonStructure([
                'message',
                'action',
                'authentication' => [
                    'type',
                    'token',
                ],
            ]);

        Event::assertDispatched(function (Registered $event) use ($data, $email, $firstName) {
            return $event->user->first_name === $firstName &&
                $event->user->email === $email &&
                $event->user->password === null;
        });
    }

    public function testGoogleAuthUnauthorized()
    {
        $this->setUpPassport();
        Event::fake([
            Registered::class,
        ]);

        /**
         * @var \Laravel\Socialite\Two\GoogleProvider
         */
        $driver = Socialite::driver('google');

        $mock = new MockHandler([
            new Response(401, ['Content-Type' => 'application/json'], json_encode($data = [
                //
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $driver->setHttpClient($client);

        $response = $this->postJson(route('api.v1.user.auth.social', [
            'driver' => 'google'
        ]), [
            'token' => 'sngdsdhvjdvgfsjdfvjshvbsjfvhfsjbvhjfbv '
        ]);

        $response->assertStatus(JsonResponse::HTTP_UNAUTHORIZED)
            ->assertJsonPath('message', 'Unauthorized.');
        Event::assertNotDispatched(Registered::class);
    }

    public function testGetTokenFromActivationCode()
    {
        TestTime::freeze();
        $user = User::factory()->create();
        $code = ActivationCode::factory()->owner($user)->create([
            'action' => 'reset_password',
            'token' => ActivationCode::hash($otp = '123456')
        ]);

        $response = $this->postJson(route('api.v1.user.auth.codeToToken'), [
            'code' => $otp,
            'action' => $code->action,
            'email' => $user->email
        ]);

        $response->assertStatus(JsonResponse::HTTP_ACCEPTED)
            ->assertJsonPath('message', 'Code verified.')
                ->assertJsonStructure([
                    'message',
                    'token',
                    'expires_at'
                ]);
    }

    public function testGetTokenFromActivationCodeError()
    {
        TestTime::freeze();
        $user = User::factory()->create();
        $code = ActivationCode::factory()->owner($user)->create([
            'action' => 'reset_password',
            'token' => ActivationCode::hash($otp = '123456')
        ]);

        $response = $this->postJson(route('api.v1.user.auth.codeToToken'), [
            'code' => $otp,
            'action' => $code->action,
            'email' => 'wale@wale.com'
        ]);

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
                ->assertJsonStructure([
                    'message',
                    'errors' => [
                        'code'
                    ]
                ]);
    }

    public function testSudoTokenFromPassword()
    {
        TestTime::freeze();
        $user = $this->loginUser(User::factory()->create([
            'password' => 'password'
        ]));

        $data = [
            'user_id' => $user->id,
            'expires_at' => now()->addMinutes(60)->getTimestamp()
        ];

        $response = $this->postJson(route('api.v1.user.auth.sudo'), [
            'type' => SudoTypeEnum::PASSWORD,
            'password' => 'password'
        ]);

        $response->assertStatus(JsonResponse::HTTP_CREATED)
                ->assertJsonPath('message', 'Sudo token created successfully.')
                ->assertJsonPath('expires_at', $data['expires_at'])
                ->assertJsonStructure([
                    'message',
                    'token',
                    'expires_at'
                ]);
    }

    public function testSudoTokenFromPasswordError()
    {
        TestTime::freeze();
        $this->loginUser(User::factory()->create([
            'password' => 'password'
        ]));

        $response = $this->postJson(route('api.v1.user.auth.sudo'), [
            'type' => SudoTypeEnum::PASSWORD,
            'password' => 'passwor'
        ]);

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'password'
                ]
            ]);
    }

    public function testSudoTokenFromPasswordGuest()
    {
        TestTime::freeze();
        User::factory()->create([
            'password' => 'password'
        ]);

        $response = $this->postJson(route('api.v1.user.auth.sudo'), [
            'type' => SudoTypeEnum::PASSWORD,
            'password' => 'passwor'
        ]);

        $response->assertStatus(JsonResponse::HTTP_UNAUTHORIZED)
            ->assertJsonStructure([
                'message'
            ]);
    }

    public function testResendVerifyPhoneCode()
    {
        Termii::fake();
        Termii::mock('send', Sequence::create(new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode($data = [
                'message_id' => '9122821270554876574',
                'message' => 'Successfully Sent',
                'balance' => 9,
                'user' => 'Peter Mcleish'
            ])
        )));

        $user = $this->loginUser();
        $user->forceFill([
            'phone_verified_at' => null
        ])->update();

        $response = $this->postJson(route('api.v1.user.auth.phone.resend'));

        $response->assertStatus(JsonResponse::HTTP_ACCEPTED)
                ->assertJsonPath(
                    'message',
                    'OTP sent to phone.'
                )
                ->assertJsonStructure([
                    'message',
                ]);

        $this->assertTrue($user->activationCodes()->whereAction('verify_phone')->count() === 1);
        //Termii::assertSentSuccessful('send');
    }

    public function testVerifyPhone()
    {
        $user = $this->loginUser();
        $user->forceFill([
            'phone_verified_at' => null
        ])->update();
        $code = ActivationCode::factory()->owner($user)->create([
            'action' => 'verify_phone',
            'token' => ActivationCode::hash($token = '123456')
        ]);

        $response = $this->postJson(route('api.v1.user.auth.phone.verify'), [
            'code' => $token
        ]);

        $response->assertStatus(JsonResponse::HTTP_ACCEPTED)
                ->assertJsonPath(
                    'message',
                    'Phone number verified successfully.'
                )
                ->assertJsonStructure([
                    'message',
                ]);

        $this->assertTrue(
            $user->activationCodes()
                ->whereAction($code->action)
                ->whereToken($code->token)->count() === 0
        );
    }

    public function testLogout()
    {
        $this->setUpPassport();
        $user = $this->newUser();

        $login = $this->postJson(route('api.v1.user.auth.login'), [
            'handle' => $user->email,
            'password' => 'password'
        ]);

        $login->assertStatus(JsonResponse::HTTP_OK);

        $response = $this->postJson(route('api.v1.user.auth.logout'), [], [
            'Authorization' => 'Bearer ' . $login->json('authentication.token')
        ]);

        $response->assertStatus(JsonResponse::HTTP_OK)
            ->assertJsonPath(
                'message',
                'Log out successful.'
            )
            ->assertJsonStructure([
                'message',
            ]);
    }
}
