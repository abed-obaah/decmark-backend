<?php

namespace App\Services\Webhook\Drivers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

interface WebhookInterface
{
    /**
     * Get driver name
     *
     * @return string
     */
    public function name(): string;

    /**
     * Validete webhook
     *
     * @param Request $request
     * @param array $data
     * @param string $raw
     * @return bool
     */
    public function validate(Request $request, array $data, string $raw): bool;

    /**
     * Process webhook
     *
     * @param Request $request
     * @param array $data
     * @param string $raw
     * @return Response
     */
    public function process(Request $request, array $data, string $raw): Response;
}
