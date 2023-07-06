<?php

namespace App\Services\Wallet;

use App\Models\BankCard;
use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Actions\ActionInterface;
use Walletable\Models\Transaction;

class CardTopUpAction implements ActionInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(Transaction $transaction, ActionData $data)
    {
        /**
         * @var BankCard
         */
        $card = $data->argument(0)->isA(BankCard::class)->value();

        $transaction->forceFill([
            'action' => 'card_topup',
            'method_id' => $card->getKey(),
            'method_type' => $card->getMorphClass()
        ])->setRelation('method', $card);
    }

    /**
     * {@inheritdoc}
     */
    public function title(Transaction $transaction)
    {
        return 'Funding with card';
    }

    /**
     * {@inheritdoc}
     */
    public function image(Transaction $transaction)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function details(Transaction $transaction)
    {
        return \collect([]);
    }

    /**
     * {@inheritdoc}
     */
    public function supportDebit(): bool
    {
        return false;
    }


    /**
     * {@inheritdoc}
     */
    public function supportCredit(): bool
    {
        return true;
    }
}
