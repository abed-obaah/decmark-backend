<?php

namespace App\Http\Controllers\Api\One\Admin;

use App\Models\Admin;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\User\RequestResetPassword;
use App\Services\Utils;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\User\ResetPasswordRequest;
use App\Mail\User\ResetPasswordMail;
use App\Models\ActivationCode;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    /**
     * Create new instance
     */
    public function __construct()
    {
        $this->middleware('guest:api-admin')->only(
            'login',
        );
        $this->middleware('auth:api')->only('logout');
    }

    /**
     * Admin login action
     *
     * @param App\Http\Request\Admin\LoginRequest $request
     * @return Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if ($admin = $this->attemptsLogin($request)) {
            return $this->sendTokenResponse($admin);
        }

        $this->failed();
    }

    /**
     * Admin login action
     *
     * @param App\Http\Request\Admin\LoginRequest $request
     * @return \App\Models\Admin|bool
     */
    protected function attemptsLogin(LoginRequest $request)
    {
        $admin = $this->getAdmin($request);

        if (!$admin) {
            return false;
        }

        if (Hash::check($request->password, $admin->password)) {
            return $admin;
        }

        $this->failed();
    }

    /**
     * Admin login action
     *
     * @param \App\Models\Admin $admin
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendTokenResponse(Admin $admin): JsonResponse
    {
        $token = $admin->createToken(request()->server('HTTP_USER_AGENT') . ':' . request()->ip())->accessToken;

        return response()->json([
            'message' => 'Login Successful',
            'authentication' => [
                'type' => 'bearer',
                'token' => $token,
            ]
        ]);
    }

    /**
     * Get the admin via email
     *
     * @param App\Http\Request\Admin\LoginRequest $request
     * @return \App\Models\Admin
     */
    protected function getAdmin(LoginRequest $request)
    {
        return Admin::where($this->adminname(), $request->email)->first();
    }

    /**
     * Get identifier key
     *
     * @return string
     */
    protected function adminname()
    {
        return 'email';
    }

    /**
     * Throw failed login exception
     */
    public function failed()
    {
        throw ValidationException::withMessages([
            $this->adminname() => [trans('auth.failed')],
        ]);
    }

    public function requestResetPassword(RequestResetPassword $request)
    {
        if ($admin = Admin::whereEmail($request->email)->first()) {
            $i = 0;

            while ($i <= 10) {
                $code = Utils::random(4, false, true);
                $hash = ActivationCode::hash($code);
                if (ActivationCode::whereToken($hash)->whereAction('reset_password')->count() < 1) {
                    $activation = $admin->activationCodes()->create([
                        'token' => $hash,
                        'action' => 'reset_password',
                        'expires_at' => now()->addMinutes(config('auth.passwords.users.expire'))
                    ]);
                    break;
                }
            }

            if (isset($activation)) {
                Mail::to($admin)->queue(new ResetPasswordMail($code));
            }
        }

        return response()->json([
            'message' => 'We have sent an OTP to your email.'
        ], JsonResponse::HTTP_OK);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        Admin::whereId($request->tokenDecrypted['user_id'])->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'message' => 'Password reset successful.'
        ], JsonResponse::HTTP_OK);
    }
}
