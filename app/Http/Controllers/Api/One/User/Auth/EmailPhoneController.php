<?php

namespace App\Http\Controllers\Api\One\User\Auth;

use Mailjet\Resources;
use App\Services\Utils;
use Illuminate\Http\Request;
use App\Models\ActivationCode;
use Illuminate\Http\JsonResponse;
use App\Mail\User\VerifyEmailMail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Mailjet\LaravelMailjet\Facades\Mailjet;
use App\Http\Requests\User\VerifyEmailRequest;
use App\Http\Requests\User\VerifyPhoneRequest;
use ManeOlawale\Laravel\Termii\Facades\Termii;

class EmailPhoneController extends Controller
{
    /**
     * Create new instance
     */
    public function __construct()
    {
        $this->middleware('auth')->only(
            'emailResend',
            'emailVerify',
            'phoneResend',
            'phoneVerify'
        );
    }

    public function emailResend(Request $request)
    {
        /**
         * @var \App\Models\User
         */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                    'message' => 'Email already verified.'
                ], JsonResponse::HTTP_CONFLICT);
        }

        $code = Utils::random(4, false, true);
        $hash = ActivationCode::hash($code);
        $user->activationCodes()->create([
            'token' => $hash,
            'action' => 'verify_email',
            'expires_at' => now()->addMinutes(30)
        ]);

        // $body = [ 'FromEmail' => env('MAIL_FROM_ADDRESS'),
        //          'FromName' => env('MAIL_FROM_NAME'), 
        //          'Recipients' => [[ 'Email' => "$user->email", 'Name' => "$user->first_name" ] ],
        //           'Subject' => "DecMark - Verify your email address.",
        //            'Text-part' => "Hey $user->first_name, \nHere is your email verification code. \n$code",
        //             'Html-part' => "Hey $user->first_name, \nHere is your email verification code. \n<code>$code</code>" ];

        // Mailjet::post(Resources::$Email, ['body' => $body]);
        Mail::to($user)->queue(new VerifyEmailMail($code));

        return response()->json([
                'message' => 'Verification email resent.'
            ], JsonResponse::HTTP_ACCEPTED);
    }

    public function emailVerify(VerifyEmailRequest $request)
    {
        /**
         * @var \App\Models\User
         */
        $user = $request->user();
        $user->activationCodes()->whereAction($request->codeModel->action)->delete();

        if (!$user->markEmailAsVerified()) {
            return response()->json([
                'message' => 'Unable to verify your email.'
            ], JsonResponse::HTTP_SERVICE_UNAVAILABLE);
        }

        return response()->json([
            'message' => 'Email verified successfully.'
        ], JsonResponse::HTTP_ACCEPTED);
    }

    public function phoneResend(Request $request)
    {
        $user = $request->user();

        if (!is_null($user->phone_verified_at)) {
            return response()->json([
                    'message' => 'Phone number already verified.'
                ], JsonResponse::HTTP_CONFLICT);
        }

        $code = Utils::random(4, false, true);
        $hash = ActivationCode::hash($code);
        $user->activationCodes()->create([
            'token' => $hash,
            'action' => 'verify_phone',
            'expires_at' => now()->addMinutes(30)
        ]);

         $sms = Termii::send($user->phone, $code . ' is your phone number verification code.');

        if (!isset($sms['message_id'])) {
            return response()->json([
                    'message' => 'Sevice is currently unavailable.'
                ], JsonResponse::HTTP_SERVICE_UNAVAILABLE);
        } 

        return response()->json([
                'message' => 'OTP sent to phone.'
            ], JsonResponse::HTTP_ACCEPTED);
    }

    public function phoneVerify(VerifyPhoneRequest $request)
    {
        $request->user()->activationCodes()->whereAction($request->codeModel->action)->delete();

        if (!$request->user()->markEmailAsVerified()) {
            return response()->json([
                'message' => 'Unable to verify your phone number.'
            ], JsonResponse::HTTP_SERVICE_UNAVAILABLE);
        }

        return response()->json([
            'message' => 'Phone number verified successfully.'
        ], JsonResponse::HTTP_ACCEPTED);
    }
}
