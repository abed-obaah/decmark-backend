<?php

namespace Tests\Unit\Webhook;

use App\Services\Webhook\Drivers\WebhookInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TestWebhookDriver implements WebhookInterface
{
    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return $this->name;
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
                'jehrfvuyjerfrhjvrfhgwfv'
            ),
            $request->header('signature')
        );
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
