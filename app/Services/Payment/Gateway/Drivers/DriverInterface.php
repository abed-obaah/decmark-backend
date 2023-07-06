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
use Walletable\Money\Money;

interface DriverInterface
{
    /**
     * Get driver name
     *
     * @return string
     */
    public function name(): string;

    /**
     * Fetch transaction
     *
     * @param string $reference
     * @return ?Transaction
     */
    public function transaction(string $reference): ?Transaction;

    /**
     * Create a checkout
     *
     * @param string $reference
     * @param LabelInterface $label
     * @param Customer $customer
     * @param Money $amount
     * @param Products $products
     * @param Meta $meta
     * @param Customization $customization
     * @return Checkout
     */
    public function checkout(
        string $reference,
        LabelInterface $label,
        Customer $customer,
        Money $amount,
        Products $products,
        Meta $meta,
        Customization $customization = null
    ): Checkout;

    /**
     * Create a checkout that supports only card
     *
     * @param string $reference
     * @param Customer $customer
     * @param LabelInterface $label
     * @param Money $amount
     * @param Products $products
     * @param Meta $meta
     * @param Customization $customization
     * @return Checkout
     */
    public function cardOnly(
        string $reference,
        LabelInterface $label,
        Customer $customer,
        Money $amount,
        Products $products,
        Meta $meta,
        Customization $customization = null
    ): Checkout;

    /**
     * Direct token charge
     *
     * @param string $token
     * @param string $reference
     * @param LabelInterface $label
     * @param Customer $customer
     * @param Money $amount
     * @param Products $products
     * @param Meta $meta
     * @return Checkout
     */
    public function token(
        string $token,
        string $reference,
        LabelInterface $label,
        Customer $customer,
        Money $amount,
        Products $products,
        Meta $meta
    ): Transaction;

    /**
     * Calculate the charges for an amount
     *
     * @param Money $amount
     * @return Money
     */
    public function charge(Money $amount): Money;

    /**
     * Initiate Refund
     *
     * @param string $reference
     * @param LabelInterface $label
     * @param ?string $reason
     * @return Refund
     */
    public function refund(string $reference, LabelInterface $label, string $reason = null): Refund;

    /**
     * Initiate a partial Refund
     *
     * @param string $reference
     * @param LabelInterface $label
     * @param Money $amount
     * @param ?string $reason
     * @return Refund
     */
    public function partialRefund(
        string $reference,
        LabelInterface $label,
        Money $amount,
        string $reason = null
    ): Refund;
}
