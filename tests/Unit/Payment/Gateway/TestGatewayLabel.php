<?php

namespace Tests\Unit\Payment\Gateway;

use App\Services\Payment\Gateway\LabelInterface;
use App\Services\Payment\Gateway\Refund;
use App\Services\Payment\Gateway\Transaction;

class TestGatewayLabel implements LabelInterface
{
    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return 'test';
    }

    /**
     * @inheritDoc
     */
    public function displayName(Transaction $transaction = null): string
    {
        return is_null($transaction) ? 'Testing' : 'Test For ' . $transaction->reference();
    }

    /**
     * @inheritDoc
     */
    public function success(Transaction $transaction)
    {
        $transaction->meta()->add('marked', 'success');
    }

    /**
     * @inheritDoc
     */
    public function failed(Transaction $transaction)
    {
        $transaction->meta()->add('marked', 'failed');
    }

    /**
     * @inheritDoc
     */
    public function reversed(Transaction $transaction)
    {
        $transaction->meta()->add('marked', 'reversed');
    }

    /**
     * @inheritDoc
     */
    public function processing(Transaction $transaction)
    {
        $transaction->meta()->add('marked', 'processing');
    }

    /**
     * @inheritDoc
     */
    public function refund(Refund $refund)
    {
        $refund->transaction()->meta()->add('marked', 'refund');
    }
}
