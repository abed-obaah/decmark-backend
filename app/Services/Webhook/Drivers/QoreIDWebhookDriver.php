<?php

namespace App\Services\Webhook\Drivers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Mail\User\VerifyEmailMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Services\Payment\Gateway\Gateway;
use Symfony\Component\HttpFoundation\Response;

class QoreIDWebhookDriver implements WebhookInterface
{
    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return 'qoreid';
    }

    /**
     * @inheritDoc
     */
    public function validate(Request $request, array $data, string $raw): bool
    {
        try {
            return hash_equals(
                hash_hmac(
                    'sha512',
                    $raw,
                    config('services.qoreid.secret')
                ),
                $request->header('x-verifyme-signature')
            );
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function process(Request $request, array $data, string $raw): Response
    {

        return response()->json([
            'status' => 'processed'
        ], JsonResponse::HTTP_OK);
    }
}
