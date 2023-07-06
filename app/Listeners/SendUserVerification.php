<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\Utils;
use App\Models\ActivationCode;
use App\Mail\User\VerifyEmailMail;
use Illuminate\Support\Facades\Mail;
use ManeOlawale\Laravel\Termii\Facades\Termii;

class SendUserVerification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $this->sendVerificationEmail($event->user);
        $this->sendVerificationPhone($event->user);
    }

    /**
     * Send verification email
     */
    public function sendVerificationEmail(User $user)
    {
        $code = Utils::random(4, false, true);
        $hash = ActivationCode::hash($code);
        $user->activationCodes()->create([
            'token' => $hash,
            'action' => 'verify_email',
            'expires_at' => now()->addMinutes(30)
        ]);

        Mail::to($user)->queue(new VerifyEmailMail($code));
    }

    /**
     * Send verification phone
     */
    public function sendVerificationPhone(User $user)
    {
        $code = Utils::random(4, false, true);
        $hash = ActivationCode::hash($code);
        $user->activationCodes()->create([
            'token' => $hash,
            'action' => 'verify_phone',
            'expires_at' => now()->addMinutes(30)
        ]);

        //Termii::send($user->phone, $code . ' is your phone number verification code.');
    }
}
