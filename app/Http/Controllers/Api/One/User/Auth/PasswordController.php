<?php

namespace App\Http\Controllers\Api\One\User\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\RequestResetPassword;
use App\Http\Requests\User\ResetPasswordRequest;
use App\Mail\User\ResetPasswordMail;
use App\Models\ActivationCode;
use App\Models\User;
use App\Services\Utils;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordController extends Controller
{
    /**
     * Create new instance
     */
    public function __construct()
    {
        $this->middleware('guest')->only(
            'requestResetPassword',
            'resetPassword'
        );
        $this->middleware('auth')->only('changePassword');
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        /**
         * @var \App\Model\User
         */
        $user = Auth::user();

        $user->update([
            'password' => $request->password
        ]);

        return response()->json([
            'message' => 'Password updated successfully.'
        ], JsonResponse::HTTP_ACCEPTED);
    }

    public function requestResetPassword(RequestResetPassword $request)
    {
        if ($user = User::whereEmail($request->email)->first()) {
            $i = 0;

            while ($i <= 10) {
                $code = Utils::random(4, false, true);
                $hash = ActivationCode::hash($code);
                if (ActivationCode::whereToken($hash)->whereAction('reset_password')->count() < 1) {
                    $activation = $user->activationCodes()->create([
                        'token' => $hash,
                        'action' => 'reset_password',
                        'expires_at' => now()->addMinutes(config('auth.passwords.users.expire'))
                    ]);
                    break;
                }
            }

            if (isset($activation)) {
                //dd($activation);
                Mail::to($user)->queue(new ResetPasswordMail($code));
            }
        }

        return response()->json([
            'message' => 'We have sent an OTP to your email.'
        ], JsonResponse::HTTP_OK);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        User::whereId($request->tokenDecrypted['user_id'])->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'message' => 'Password reset successful.'
        ], JsonResponse::HTTP_OK);
    }
}
