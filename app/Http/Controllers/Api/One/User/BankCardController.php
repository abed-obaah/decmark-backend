<?php

namespace App\Http\Controllers\Api\One\User;

use App\Models\BankCard;
use Illuminate\Support\Str;
use Walletable\Money\Money;
use Illuminate\Http\Request;
use App\Classes\SenderHelper;
use App\Enums\AppScreensEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Payment\Gateway\Meta;
use App\Http\Resources\BankCardResource;
use App\Services\Payment\Gateway\Gateway;
use App\Services\Payment\Gateway\Product;
use App\Http\Resources\BankCardCollection;
use App\Services\Payment\Gateway\Customer;
use App\Services\Payment\Gateway\Products;
use App\Services\Payment\Gateway\Customization;
use App\Http\Requests\User\BankCard\StartRequest;

class BankCardController extends Controller
{
    public function index(Request $request)
    {
        return new BankCardCollection($request->user()->bankCards()->get());
    }

    public function show(BankCard $card)
    {
        return new BankCardResource($card);
    }

    public function delete(Request $request, BankCard $card)
    {
        if ($card->delete()) {
            SenderHelper::userLog($request->user(), 'You deleted a bank card. label: '.$card->label , AppScreensEnum::WALLET);
            
            return response()->json([
                'message' => 'Bank card deleted.'
            ], JsonResponse::HTTP_OK);
        }

        return response()->json([
            'message' => 'Unable to delete card.'
        ], JsonResponse::HTTP_BAD_REQUEST);
    }

    public function store(StartRequest $request)
    {
        /**
         * @var \App\Models\User
         */
        $user = $request->user();

        DB::beginTransaction();
        $card = $user->bankCards()->create([
            'label' => $request->label,
            'reference' => Str::orderedUuid(),
            'driver' => 'paystack',
        ]);

        $checkout = Gateway::driver('paystack')->cardOnly(
            $card->reference,
            Gateway::label('bank_card'),
            new Customer(
                $user->email,
                Customer::TYPE_EMAIL,
                $user->first_name . ' ' . $user->last_name,
                $user->email,
                $user->phone
            ),
            $money = Money::NGN(10000),
            new Products(
                new Product(
                    $card->label,
                    $money,
                    1,
                    $card
                )
            ),
            Meta::data([
                'card_id' => $card->id
            ]),
            new Customization('Add a Debit Card')
        );
        DB::commit();

        return response()->json([
            'message' => 'Initiated successfullly.',
            'url' => $checkout->checkout(),
            'id' => $card->id,
            'reference' => $card->reference

        ], JsonResponse::HTTP_OK);
    }
}
