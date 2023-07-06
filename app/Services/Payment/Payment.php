<?php

namespace App\Services\Payment;

use App\Services\Payment\Methods\PaymentMethod;
use Exception;
use Illuminate\Contracts\Support\Responsable;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;
use Walletable\Money\Money;

class Payment implements Responsable
{
    /**
     * Success Payment
     *
     * @var string
     */
    public const SUCCESS = 'SUCCESS';

    /**
     * Ongoing
     *
     * @var string
     */
    public const ONGOING = 'ONGOING';

    /**
     * Failed Payment
     *
     * @var string
     */
    public const FAILED = 'FAILED';

    /**
     * Driver that generated the order
     *
     * @var PaymentMethod
     */
    protected $method;

    /**
     * Reference
     *
     * @var string
     */
    protected $reference;

    /**
     * Reference
     *
     * @var string
     */
    protected $secondaryReference;

    /**
     * Method name
     *
     * @var string
     */
    protected $name;

    /**
     * Payment status
     *
     * @var string
     */
    protected $status;

    /**
     * Amount paid
     *
     * @var Money
     */
    protected $amount;

    /**
     * Fee for payment service
     *
     * @var Money
     */
    protected $fee;

    /**
     * Model of product paid for
     *
     * @var Model
     */
    protected $model;

    /**
     * Http Response
     *
     * @var Response
     */
    protected $response;

    /**
     * Create new airtime order
     *
     * @param PaymentMethod $method
     * @param string $reference
     * @param string $secondaryReference
     * @param string $name
     * @param Money $amount
     * @param string $status = Payment::SUCCESS
     */
    public function __construct(
        PaymentMethod $method,
        string $reference,
        string $secondaryReference,
        string $name,
        Money $amount,
        Money $fee,
        string $status = Payment::SUCCESS
    ) {
        $this->method = $method;
        $this->reference = $reference;
        $this->secondaryReference = $secondaryReference;
        $this->name = $name;
        $this->amount = $amount;
        $this->fee = $fee;

        $statuses = \array_values((new ReflectionClass(self::class))->getConstants());

        if (!in_array($status, $statuses)) {
            throw new Exception(sprintf('Status must be in [%s]', implode(',', $statuses)));
        }

        $this->status = $status;
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

    /**
     * Check is has http response
     *
     * @return bool
     */
    public function hasResponse(): bool
    {
        return isset($this->response);
    }

    /**
     * Get payment status
     *
     * @return string
     */
    public function status()
    {
        return $this->status;
    }

    /**
     * Get payment method
     *
     * @return PaymentMethod
     */
    public function method(): PaymentMethod
    {
        return $this->method;
    }

    /**
     * Get payment reference
     *
     * @return string
     */
    public function reference()
    {
        return $this->reference;
    }

    /**
     * Get payment secondary reference
     *
     * @return string
     */
    public function secondaryReference()
    {
        return $this->secondaryReference;
    }

    /**
     * Get payment name
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Get payment amount
     *
     * @return Money
     */
    public function amount(): Money
    {
        return $this->amount;
    }

    /**
     * Get payment fee
     *
     * @return Money
     */
    public function fee(): Money
    {
        return $this->fee;
    }

    /**
     * Check status
     *
     * @return bool
     */
    public function check(string $status): bool
    {
        return $this->status === $status;
    }

    public function toResponse($request): Response
    {
        return $this->response;
    }
}
