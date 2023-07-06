<?php

namespace App\Services\Webhook\Drivers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MockWebhookDriver implements WebhookInterface
{
    /**
     * Driver name
     *
     * @var string
     */
    protected $name;

    /**
     * Don`t validate webhook
     *
     * @var boolean
     */
    protected $dontValidate = false;

    /**
     * Webhook response
     *
     * @var boolean
     */
    protected $response;

    public function __construct(
        string $name = 'mock'
    ) {
        $this->name = $name;
    }

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
        return !$this->dontValidate;
    }

    /**
     * @inheritDoc
     */
    public function process(Request $request, array $data, string $raw): Response
    {
        return $this->response ?? response()->json([
            'status' => 'processed'
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Don`t validate webhook
     *
     * @param boolean $dontValidate
     * @return self
     */
    public function dontValidate(bool $dontValidate = true): self
    {
        $this->dontValidate = $dontValidate;

        return $this;
    }

    /**
     * Set http response
     *
     * @return self
     */
    public function setResponse(Response $response): self
    {
        $this->response = $response;

        return $this;
    }
}
