<?php

namespace App\Http\Controllers\Api\One\User\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ChangePinRequest;
use App\Http\Requests\User\CodeToTokenRequest;
use App\Http\Requests\User\CreatePinRequest;
use App\Http\Requests\User\SocialLoginRequest;
use App\Http\Requests\User\SudoRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\TokenRepository;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * Create new instance
     */
    public function __construct()
    {
        $this->middleware('guest')->only(
            'soicialLogin'
        );

        $this->middleware('auth')->only('sudo', 'pinCreate', 'pinChange', 'logout');
    }

    public function soicialLogin(SocialLoginRequest $request, string $driver)
    {
        /**
         * @var \Laravel\Socialite\Two\GoogleProvider
         */
        $driver = Socialite::driver($driver);

        try {
            $user = $driver->userFromToken($request->token);

            if ($userModel = User::whereEmail($user->email)->first()) {
                return $this->loginResponse($userModel);
            } elseif ($user->email) {
                event(new Registered($userModel = User::create([
                    'last_name' => explode(' ', $user->name)[1] ?? 'Null',
                    'first_name' => explode(' ', $user->name)[0] ?? 'Null',
                    'email' => $user->email
                ])));

                return $this->loginResponse($userModel, 'Registration successful.', JsonResponse::HTTP_CREATED);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }
    }

    protected function loginResponse(
        User $user,
        string $message = 'Login Successful.',
        int $responseCode = JsonResponse::HTTP_OK
    ) {
        $token = $user->createToken(request()->server('HTTP_USER_AGENT') . ':' . request()->ip())->accessToken;

        return response()->json([
            'message' => $message,
            'action' => $responseCode === JsonResponse::HTTP_CREATED ? 'registration' : 'login',
            'authentication' => [
                'type' => 'bearer',
                'token' => $token,
            ]
        ], $responseCode);
    }

    public function codeToToken(CodeToTokenRequest $request)
    {
        $data = [
            'user_id' => $request->codeModel->owner->id,
            'code' => $request->code,
            'action' => $request->action,
            'expires_at' => now()->addMinutes(10)->getTimestamp()
        ];

        $request->codeModel->owner
            ->activationCodes()->whereAction($request->codeModel->action)->delete();

        return response()->json([
            'message' => 'Code verified.',
            'token' => Crypt::encryptString(json_encode($data)),
            'expires_at' => $data['expires_at']
        ], JsonResponse::HTTP_ACCEPTED);
    }

    public function sudo(SudoRequest $request)
    {
        $user = $request->user();

        $data = [
            'user_id' => $user->id,
            'uuid' => Str::orderedUuid(),
            'expires_at' => now()->addMinutes(60)->getTimestamp()
        ];

        return response()->json([
            'message' => 'Sudo token created successfully.',
            'token' => Crypt::encryptString(json_encode($data)),
            'expires_at' => $data['expires_at']
        ], JsonResponse::HTTP_CREATED);
    }

    public function pinCreate(CreatePinRequest $request)
    {
        /**
         * @var \App\Models\User
         */
        $user = $request->user();

        if (!is_null($user->pin)) {
            return response()->json([
                    'message' => 'Pin already created.'
                ], JsonResponse::HTTP_CONFLICT);
        }

        if (
            !$user->update([
                'pin' => Hash::make($request->pin)
            ])
        ) {
            return response()->json([
                    'message' => 'Sevice is currently unavailable.'
                ], JsonResponse::HTTP_SERVICE_UNAVAILABLE);
        }

        return response()->json([
            'message' => 'Pin created successfully.'
        ], JsonResponse::HTTP_CREATED);
    }

    public function pinChange(ChangePinRequest $request)
    {
        /**
         * @var \App\Model\User
         */
        $user = $request->user();

        $user->update([
            'pin' => Hash::make($request->pin)
        ]);

        return response()->json([
            'message' => 'Pin updated successfully.'
        ], JsonResponse::HTTP_ACCEPTED);
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout(Request $request, TokenRepository $tokenRepository): JsonResponse
    {
        \Laravel\Passport\Token::class;
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Log out successful.'
        ], JsonResponse::HTTP_OK);
    }
}
