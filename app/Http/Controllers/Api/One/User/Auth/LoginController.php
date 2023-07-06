<?php

namespace App\Http\Controllers\Api\One\User\Auth;

use App\Http\Controllers\Api\One\User\Controller;
use App\Http\Requests\User\LoginRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    /**
     * Create new instance
     */
    public function __construct()
    {
        $this->middleware('guest')->only(
            'login'
        );
        $this->middleware('auth')->only('logout');
    }

    /**
     * User login action
     *
     * @param \App\Http\Requests\User\LoginRequest $request
     * @return Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if ($user = $this->attemptsLogin($request)) {
            return $this->sendTokenResponse($user);
        }

        $this->failed($request);
    }

    /**
     * User login action
     *
     * @param \App\Http\Requests\User\LoginRequest $request
     * @return \App\Models\User|bool
     */
    protected function attemptsLogin(LoginRequest $request)
    {
        $user = $this->getUser($request);

        if (!$user) {
            return false;
        }

        if (Hash::check($request->password, $user->password)) {
            return $user;
        }

        $this->failed($request);
    }

    /**
     * User login action
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendTokenResponse(User $user): JsonResponse
    {
        $token = $user->createToken(request()->server('HTTP_USER_AGENT') . ':' . request()->ip())->accessToken;

        return response()->json([
            'message' => 'Login Successful.',
            'data' => [
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'profile_img' => $user->profile_img,
            ],
            'authentication' => [
                'type' => 'bearer',
                'token' => $token,
            ]
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Get the user via email
     *
     * @param \App\Http\Requests\User\LoginRequest $request
     * @return \App\Models\User
     */
    protected function getUser(LoginRequest $request)
    {
        return User::where($request->handle_type, $request->handle)->first();
    }

    /**
     * Throw failed login exception
     */
    public function failed(LoginRequest $request)
    {
        throw ValidationException::withMessages([
            $request->handle_type => [trans('auth.failed')],
        ]);
    }
}
