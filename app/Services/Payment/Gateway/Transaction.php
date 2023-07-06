<?php

namespace App\Services\Payment\Gateway;

use App\Services\Payment\Gateway\Drivers\DriverInterface;
use Walletable\Money\Money;

class Transaction
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
     * Processing status
     *
     * @var string
     */
    public const PROCESSING = 'PROCESSING';

    /**
     * Failed status
     *
     * @var string
     */
    public const REVERSED = 'REVERSED';

    /**
     * Transaction label
     *
     * @var LabelInterface
     */
    protected $label;

    /**
     * Transaction Card
     *
     * @var Card
     */
    protected $card = null;

    /**
     * create new instance
     *
     * @param DriverInterface $driver
     * @param string $reference
     * @param string $status
     * @param LabelInterface $label
     * @param Money $amount
     * @param Money $charge
     * @param Customer $customer
     * @param Products $products
     * @param Meta $meta
     * @param Card $card
     */
    public function __construct(
        DriverInterface $driver,
        string $reference,
        string $status,
        LabelInterface $label,
        Money $amount,
        Money $charge,
        Customer $customer,
        Products $products,
        Meta $meta,
        Card $card = null
    ) {
        $this->validateStatus($status);

        $this->driver = $driver;
        $this->reference = $reference;
        $this->label = $label;
        $this->status = $status;
        $this->amount = $amount;
        $this->charge = $charge;
        $this->customer = $customer;
        $this->products = $products;
        $this->meta = $meta;
        $this->card = $card;
    }

    /**
     * Transaction label
     *
     * @return LabelInterface
     */
    public function label(): LabelInterface
    {
        return $this->label;
    }

    /**
     * Transaction Card
     *
     * @return Card
     */
    public function card(): ?Card
    {
        return $this->card;
    }

    /**
     * Check if the transaction used a card
     *
     * @return boolean
     */
    public function hasCard(): bool
    {
        return !is_null($this->card);
    }

    /**
     * Initiate Refund
     *
     * @param string $reason
     * @return Refund
     */
    public function refund(string $reason = null): Refund
    {
        return $this->driver->refund($this->reference, $this->label, $reason);
    }

    /**
     * Initiate a partial Refund
     *
     * @param Money $amount
     * @param string $reason
     * @return Refund
     */
    public function partialRefund(
        Money $amount,
        string $reason = null
    ): Refund {
        return $this->driver->partialRefund($this->reference, $this->label, $amount, $reason);
    }
}
