<?php

namespace App\Services\Payment\Labels;

use App\Models\BankCard;
use App\Services\Payment\Gateway\Gateway;
use App\Services\Payment\Gateway\LabelInterface;
use App\Services\Payment\Gateway\Refund;
use App\Services\Payment\Gateway\Transaction;

class CardGatewayLabel implements LabelInterface
{
    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return 'bank_card';
    }

    /**
     * @inheritDoc
     */
    public function displayName(Transaction $transaction = null): string
    {
        return 'Bank Card';
    }

    /**
     * @inheritDoc
     */
    public function success(Transaction $transaction)
    {
        $product = $transaction->products()->first();

        if (!$product->hasModel()) {
            return;
        }

        if (!$product->modelLoaded()) {
            $product->loadModel();
        }

        /**
         * @var \App\Models\BankCard
         */
        if (!(($bankCard = $product->model()) instanceof BankCard)) {
            return;
        }

        if ($transaction->hasCard() && !$bankCard->token) {
            $card = $transaction->card();

            if (!$card->authorized()) {
                $bankCard->delete();
                $refund = $transaction->refund('Refund Bank card fee.');
                $refund->transaction()->products()->first()->setModel($bankCard);
                Gateway::processRefund($refund->label(), $refund);
                return;
            }

            $bankCard->update([
                'reference' => $transaction->reference(),
                'name' => $card->name(),
                'number' => $card->number(),
                'expiry_month' => $card->expiryMonth(),
                'expiry_year' => $card->expiryYear(),
                'brand' => $card->brand(),
                'token' => $card->authorization(),
                'paid_at' => now()
            ]);

            $refund = $transaction->refund('Refund Bank card fee.');
            $refund->transaction()->products()->first()->setModel($bankCard);
            Gateway::processRefund($refund->label(), $refund);
        } else {
            $bankCard->delete();
        }
    }

    /**
     * @inheritDoc
     */
    public function failed(Transaction $transaction)
    {
        $transaction->products()->first()?->model()?->delete();
    }

    /**
     * @inheritDoc
     */
    public function reversed(Transaction $transaction)
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function processing(Transaction $transaction)
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function refund(Refund $refund)
    {
        $refund->transaction()->products()->first()?->model()?->update([
            'refunded_at' => now()
        ]);
    }
}
