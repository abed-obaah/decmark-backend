<?php

namespace App\Http\Controllers\Api\One\Admin;

use App\Models\Admin;
use App\Services\Utils;
use Illuminate\Http\Request;
use App\Models\ActivationCode;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Mail\User\ResetPasswordMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\User\RequestResetPassword;
use App\Http\Requests\User\ResetPasswordRequest;
use App\Http\Requests\Admin\UpdateProfileRequest;

class AccountController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();
        return response()->json($user);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        try {
            $data = $request->validated();
            $response = Admin::where('id', $request->user()->id)->first();
            $response->name = $data['name'];
            $response->email = $data['email'];
            $response->password = Hash::check($data['current_password'], $request->user()->password) ? Hash::make($data['new_password']) : $request->user()->password;
            $response->save();
            $response->refresh();
            return response()->json(['message' => 'Profile updated successfully', 'data' => $response]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    public function requestResetPassword(RequestResetPassword $request)
    {
        if ($user = Admin::whereEmail($request->email)->first()) {
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
        Admin::whereId($request->tokenDecrypted['user_id'])->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'message' => 'Password reset successful.'
        ], JsonResponse::HTTP_OK);
    }
}
