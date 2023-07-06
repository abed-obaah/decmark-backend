<?php

namespace App\Services\Payment\Gateway\Drivers;

use App\Services\Payment\Gateway\Checkout;
use App\Services\Payment\Gateway\Customer;
use App\Services\Payment\Gateway\Customization;
use App\Services\Payment\Gateway\LabelInterface;
use App\Services\Payment\Gateway\Meta;
use App\Services\Payment\Gateway\Products;
use App\Services\Payment\Gateway\Refund;
use App\Services\Payment\Gateway\Transaction;
use Illuminate\Support\Str;
use Walletable\Money\Money;

class MockGatewayDriver implements DriverInterface
{
    /**
     * Driver name
     *
     * @var string
     */
    protected $name;

    /**
     * Checkout mock
     *
     * @var Checkout
     */
    protected $checkout;

    /**
     * Transaction mock
     *
     * @var Transaction
     */
    protected $transaction;

    /**
     * Refund mock
     *
     * @var Refund
     */
    protected $refund;

    /**
     * Charge mock
     *
     * @var Money
     */
    protected $charge;

    public function __construct(
        string $name = 'mock'
    ) {
        $this->name = $name;
    }

    /**
     * Mock driver
     *
     * @param Checkout|null $checkout
     * @param Transaction|null $transaction
     * @param Money|null $charge
     * @return self
     */
    public function mock(
        Checkout $checkout = null,
        Transaction $transaction = null,
        Money $charge = null,
        Refund $refund = null
    ): self {
        $this->checkout = $checkout ?? $this->checkout;
        $this->transaction = $transaction ?? $this->transaction;
        $this->charge = $charge ?? $this->charge;
        $this->refund = $refund ?? $this->refund;

        return $this;
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
    public function transaction(string $reference): ?Transaction
    {
        return $this->transaction;
    }

    /**
     * @inheritDoc
     */
    public function checkout(
        string $reference,
        LabelInterface $label,
        Customer $customer,
        Money $amount,
        Products $products,
        Meta $meta,
        Customization $customization = null
    ): Checkout {
        return $this->checkout ?? new Checkout(
            $this,
            $reference,
            'https://gateway.com/pay/' . $reference,
            Checkout::SUCCESS,
            $amount,
            $this->charge ?? Money::NGN(0),
            $customer,
            $products,
            $meta
        );
    }

    /**
     * @inheritDoc
     */
    public function cardOnly(
        string $reference,
        LabelInterface $label,
        Customer $customer,
        Money $amount,
        Products $products,
        Meta $meta,
        Customization $customization = null
    ): Checkout {
        return $this->checkout ?? new Checkout(
            $this,
            $reference,
            'https://gateway.com/pay/' . $reference,
            Checkout::SUCCESS,
            $amount,
            $this->charge ?? Money::NGN(0),
            $customer,
            $products,
            $meta
        );
    }

    /**
     * @inheritDoc
     */
    public function token(
        string $token,
        string $reference,
        LabelInterface $label,
        Customer $customer,
        Money $amount,
        Products $products,
        Meta $meta
    ): Transaction {
        return $this->transaction ?? new Transaction(
            $this,
            $reference,
            Transaction::SUCCESS,
            $label,
            $amount,
            $this->charge ?? Money::NGN(0),
            $customer,
            $products,
            $meta
        );
    }

    /**
     * @inheritDoc
     */
    public function charge(Money $amount): Money
    {
        return $this->charge ?? Money::NGN(0);
    }

    /**
     * @inheritDoc
     */
    public function refund(string $reference, LabelInterface $label, string $reason = null): Refund
    {
        return $this->refund ?? new Refund(
            $this,
            Str::orderedUuid(),
            $reference,
            Refund::SUCCESS,
            Refund::FULL,
            $this->transaction->amount(),
            $label,
            $this->transaction,
            $reason
        );
    }

    /**
     * @inheritDoc
     */
    public function partialRefund(
        string $reference,
        LabelInterface $label,
        Money $amount,
        string $reason = null
    ): Refund {
        return $this->refund ?? new Refund(
            $this,
            Str::orderedUuid(),
            $reference,
            Refund::SUCCESS,
            Refund::PARTIAL,
            $amount,
            $label,
            $this->transaction,
            $reason
        );
    }
}
