<?php

namespace App\Services\Payment\Labels;

use App\Models\BankCard;
use App\Models\Transaction as ModelsTransaction;
use App\Models\Wallet;
use App\Services\Payment\Gateway\LabelInterface;
use App\Services\Payment\Gateway\Refund;
use App\Services\Payment\Gateway\Transaction;
use Walletable\Internals\Actions\ActionData;

class WalletGatewayLabel implements LabelInterface
{
    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return 'wallet';
    }

    /**
     * @inheritDoc
     */
    public function displayName(Transaction $transaction = null): string
    {
        return 'Wallet Topup';
    }

    /**
     * @inheritDoc
     */
    public function success(Transaction $transaction)
    {
        if (ModelsTransaction::whereReference($transaction->reference())->count()) {
            return;
        }

        /**
         * @var \App\Models\Wallet
         */
        if (!$wallet = Wallet::find($transaction->meta()->get('wallet_id'))) {
            return;
        }

        /**
         * @var \App\Models\BankCard
         */
        if (!$card = BankCard::find($transaction->meta()->get('card_id'))) {
            return;
        }

        $wallet->action('card_topup')->credit(
            $transaction->amount(),
            new ActionData($card)
        )->getTransactions()->first()->forceFill([
            'reference' => $transaction->reference()
        ])->save();
    }

    /**
     * @inheritDoc
     */
    public function failed(Transaction $transaction)
    {
        //
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
        //
    }
}
