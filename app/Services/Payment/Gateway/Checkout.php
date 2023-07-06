<?php

namespace App\Services\Payment\Gateway;

use App\Services\Payment\Gateway\Drivers\DriverInterface;
use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpFoundation\Response;
use Walletable\Money\Money;

class Checkout implements Responsable
{
    use PaymentTrait;
    use HasStatus;

    /**
     * Success Status
     *
     * @var string
     */
    public const SUCCESS = 'SUCCESS';

    /**
     * Failed status
     *
     * @var string
     */
    public const FAILED = 'FAILED';

    /**
     * Checkout Url
     *
     * @var string
     */
    protected $checkout;

    /**
     * create new instance
     *
     * @param DriverInterface $driver
     * @param string $reference
     * @param string $checkout
     * @param string $status
     * @param Money $amount
     * @param Money $charge
     * @param Customer $customer
     * @param Products $products
     * @param Meta $meta
     */
    public function __construct(
        DriverInterface $driver,
        string $reference,
        string $checkout,
        string $status,
        Money $amount,
        Money $charge,
        Customer $customer,
        Products $products,
        Meta $meta
    ) {
        $this->validateStatus($status);

        $this->driver = $driver;
        $this->reference = $reference;
        $this->status = $status;
        $this->amount = $amount;
        $this->charge = $charge;
        $this->checkout = $checkout;
        $this->customer = $customer;
        $this->products = $products;
        $this->meta = $meta;
    }

    /**
     * Get checkout url
     *
     * @return string
     */
    public function checkout(): string
    {
        return $this->checkout;
    }

    /**
     * @inheritDoc
     */
    public function toResponse($request): Response
    {
        return redirect()->away($this->checkout);
    }
}
