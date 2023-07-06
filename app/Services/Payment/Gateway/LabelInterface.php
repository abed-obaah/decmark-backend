<?php

namespace App\Services\Payment\Gateway;

interface LabelInterface
{
    /**
     * Get label name
     *
     * @return string
     */
    public function name(): string;

    /**
     * Get label display name
     *
     * @param ?Transaction $transaction
     * @return string
     */
    public function displayName(?Transaction $transaction = null): string;

    /**
     * Process successful transaction
     *
     * @param Transaction $transaction
     * @return void
     */
    public function success(Transaction $transaction);

    /**
     * Process failed transaction
     *
     * @param Transaction $transaction
     * @return void
     */
    public function failed(Transaction $transaction);

    /**
     * Process reversed transaction
     *
     * @param Transaction $transaction
     * @return void
     */
    public function reversed(Transaction $transaction);

    /**
     * Process processing transaction
     *
     * @param Transaction $transaction
     * @return void
     */
    public function processing(Transaction $transaction);

    /**
     * Process refund transaction
     *
     * @param Refund $transaction
     * @return void
     */
    public function refund(Refund $transaction);
}
