<?php

namespace App\Services\Payment\Methods;

use App\Http\Resources\AirtimeResource;
use App\Models\Airtime;
use App\Models\BankCard;
use App\Services\Payment\Gateway\Customer;
use App\Services\Payment\Gateway\Gateway;
use App\Services\Payment\Gateway\Meta;
use App\Services\Payment\Gateway\Products;
use App\Services\Payment\Gateway\Transaction;
use App\Services\Payment\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CardPaymentMethod implements PaymentMethod
{
    /**
     * Request instance
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Callback stack
     *
     * @var array[string=>Closure]
     */
    protected static $callbacks;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @inheritDoc
     */
    public function airtime(Airtime $airtime): Payment
    {
        $card = BankCard::find($this->request->card);
        $reference = Str::orderedUuid();

        $transaction = $card->getGateway()->token(
            $card->token,
            $reference,
            Gateway::label('airtime'),
            new Customer(
                $airtime->owner->email,
                Customer::TYPE_EMAIL,
                $airtime->owner->name,
                $airtime->owner->email,
                $airtime->owner->phone
            ),
            $airtime->amount,
            new Products(),
            Meta::data([
                'card_id' => $card->id,
                'airtime_id' => $airtime->id,
            ])
        );

        if ($transaction->check(Transaction::FAILED)) {
            $airtime->delete();
            return (new Payment(
                $this,
                '',
                '',
                'card',
                $airtime->amount,
                $card->getGateway()->charge($airtime->amount),
                Payment::FAILED
            ))->setResponse(
                response()->json([
                    'message' => 'Charge not successful.'
                ], JsonResponse::HTTP_FAILED_DEPENDENCY)
            );
        }

        return (new Payment(
            $this,
            $card->getKey(),
            $transaction->reference(),
            'card',
            $airtime->amount,
            $card->getGateway()->charge($airtime->amount),
            Payment::ONGOING
        ))->setResponse(
            response()->json([
                'message' => 'Charge is processing.',
                'airtime' => new AirtimeResource($airtime)
            ], JsonResponse::HTTP_ACCEPTED)
        );
    }

    /**
     * @inheritDoc
     */
    public function rules(Request $request): array
    {
        return [
            'card' => ['required', 'uuid', Rule::exists(BankCard::table(), 'id')
                ->where('owner_id', $request->user()->getKey())
                ->where('owner_type', $request->user()->getMorphClass())]
        ];
    }
}
