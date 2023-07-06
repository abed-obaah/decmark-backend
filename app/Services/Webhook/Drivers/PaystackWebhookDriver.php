<?php

namespace App\Services\Webhook\Drivers;

use App\Services\Payment\Gateway\Gateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaystackWebhookDriver implements WebhookInterface
{
    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return 'paystack';
    }

    /**
     * @inheritDoc
     */
    public function validate(Request $request, array $data, string $raw): bool
    {
        return hash_equals(
            hash_hmac(
                'sha512',
                $raw,
                config('services.paystack.secret')
            ),
            $request->header('x-paystack-signature')
        );
    }

    /**
     * @inheritDoc
     */
    public function process(Request $request, array $data, string $raw): Response
    {
        $driver = Gateway::driver('paystack');

        $transaction = $driver->transaction($data['data']['reference']);

        Gateway::processLabel($transaction->label(), $transaction);

        return response()->json([
            'status' => 'processed'
        ], JsonResponse::HTTP_OK);
    }
}
