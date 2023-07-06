<?php

namespace App\Http\Controllers\Api\One\User;

use App\Models\User;
use App\Models\Wallet;
use App\Models\BankCard;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Walletable\Money\Money;
use Illuminate\Http\Request;
use App\Classes\SenderHelper;
use App\Constants\AppConstants;
use App\Enums\AppScreensEnum;
use Illuminate\Http\JsonResponse;
use App\Enums\BeneficiaryTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\WalletResource;
use App\Services\Payment\Gateway\Meta;
use App\Http\Resources\DisputeResource;
use App\Http\Resources\CustomerResource;
use App\Services\Payment\Gateway\Gateway;
use App\Services\Payment\Gateway\Customer;
use App\Services\Payment\Gateway\Products;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\TransactionCollection;
use App\Http\Requests\User\Wallet\TransferRequest;
use App\Http\Requests\User\Wallet\ChangeTagRequest;
use App\Http\Requests\User\Wallet\CreateTagRequest;
use App\Http\Requests\User\Wallet\ResolveTagRequest;
use App\Http\Requests\User\Wallet\FundWithCardRequest;
use Walletable\Exceptions\InsufficientBalanceException;
use App\Http\Requests\User\Wallet\ReportTransactionRequest;
use App\Services\Payment\Gateway\Transaction as GatewayTransaction;
use Walletable\Money\Currency;

class WalletController extends Controller
{
    /**
     * Wallet index
     */
    public function index(Request $request)
    {
        /**
         * @var \App\Models\User
         */
        $user = $request->user();
        WalletResource::withoutWrapping();
        return new WalletResource($user->wallets()->first());
    }

    /**
     * Wallet to wallet transfer
     */
    public function transfer(TransferRequest $request)
    {
        /**
         * @var \App\Models\User
         */
        $user = $request->user();
        /**
         * @var \App\Models\Wallet
         */
        $wallet = $user->wallets()->first()->setRelation('walletable', $user);
        $amount = $wallet->money($request->amount);

        /**
         * @var \App\Models\User
         */
        $recipient = $request->userTag;

        if($user->id == $recipient->id){
            return response()->json([
                'success' => false,
                'message' => 'You can not transfer money to yourself.',
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        /**
         * @var \App\Models\Wallet
         */
        $recipientWallet = $recipient->wallets()->first()->setRelation('walletable', $recipient);

        try {
        
            $result = $wallet->transfer($recipientWallet, $amount, $request->remarks);
            
            $referrer = User::where('id', $recipient->referrer_id)->first();

            if(!is_null($referrer)){
                $referrer_wallet = $referrer->wallets()->first();
                if(!is_null($referrer_wallet)){
                    $referrer_wallet->update([
                    'amount' => $referrer_wallet->amount->add($amount->multiply(AppConstants::PROVIDER_DEDUCT)->multiply(AppConstants::REFERRAL_BONUS))->getInt()
                    ]);

                    $ref_row = $recipient->referral()->first();
                    if(!is_null($ref_row)){
                        $ref_row->update([
                            'month_balance' => $ref_row->month_balance + $amount->multiply(AppConstants::PROVIDER_DEDUCT)->multiply(AppConstants::REFERRAL_BONUS)->getInt()
                        ]);
                    }
                }
            }
            
            SenderHelper::appNotification($recipient, 'Credit Alert!', 'You recieved '. $amount->getCurrency()->getCode() . $request->amount / 100 .' from '. $request->user()->getNameAttribute(), AppScreensEnum::WALLET);
            SenderHelper::userLog($request->user(), 'You transferred '. $amount->getCurrency()->getCode() . $request->amount / 100 .' to '. $recipient->getNameAttribute() , AppScreensEnum::WALLET);

        } catch (InsufficientBalanceException $th) {
            return response()->json([
                'message' => 'Insuficient funds.'
            ], JsonResponse::HTTP_NOT_ACCEPTABLE);
        } catch (\Throwable $th) {
            throw $th;
        }

        return response()->json([
            'message' => 'Transaction successful.',
            'transaction' => new TransactionResource(
                $result->getTransactions()
                    ->where('type', 'debit')
                    ->first()
            )
        ], JsonResponse::HTTP_OK);
    }

    public function fundWithCard(FundWithCardRequest $request)
    {
        /**
         * @var \App\Models\User
         */
        $user = $request->user();
        /**
         * @var \App\Models\Wallet
         */
        $wallet = $user->wallets()->first()->setRelation('walletable', $user);

        /**
         * @var BankCard
         */
        $card = BankCard::find($request->card);

        if(is_null($card->token)){
            return response()->json([
                'success' => false,
                'message' => 'This card has not been initialized'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $amount = Money::NGN((int)$request->amount);

        $transaction = $card->getGateway()->token(
            $card->token,
            Str::orderedUuid(),
            Gateway::label('wallet'),
            new Customer(
                $user->email,
                Customer::TYPE_EMAIL,
                $user->first_name . ' ' . $user->last_name,
                $user->email,
                $user->phone
            ),
            $amount,
            new Products(),
            Meta::data([
                'card_id' => $card->id,
                'wallet_id' => $wallet->id,
            ])
        );

        if ($transaction->check(GatewayTransaction::FAILED)) {
            return response()->json([
                'message' => 'Charge not successful.'
            ], JsonResponse::HTTP_FAILED_DEPENDENCY);
        }

        return response()->json([
            'message' => 'Charge is processing.'
        ], JsonResponse::HTTP_ACCEPTED);

        //TODO: Log and Notifications
    }

    /**
     * Get a single transaction
     */
    public function transaction(Request $request, Transaction $transaction)
    {
        /**
         * @var \App\Models\User
         */
        $user = $request->user();

        if (
            !(Wallet::whereId($transaction->wallet_id)
                ->whereWalletableId($user->id)
                ->whereWalletableType($user->getMorphClass())->count() > 0)
        ) {
            return response()->json([
                'message' => 'Not found.'
            ], 404);
        }

        return new TransactionResource($transaction);
    }

    /**
     * Transaction history action
     */
    public function transactionHistory(Request $request)
    {
        /**
         * @var \App\Models\User
         */
        $user = $request->user();

        $transactions = $user->transactions()->paginate(20);

        return new TransactionCollection($transactions);
    }

    /**
     * Latest Transactions action
     */
    public function transactionLatest(Request $request, int $count = 1)
    {
        /**
         * @var \App\Models\User
         */
        $user = $request->user();

        $transactions = $user->transactions()->take($count)->get();

        return new TransactionCollection($transactions);
    }

    /**
     * Wallet to wallet transfer
     */
    public function reportTransaction(ReportTransactionRequest $request)
    {
        /**
         * @var \App\Models\Transaction
         */
        $transaction = Transaction::find($request->transaction);

        $dispute = $transaction->dispute(
            $request->user(),
            $request->flags,
            $request->title,
            $request->note
        )->setRelation('disputable', $transaction);

        DisputeResource::withoutWrapping();

        return response()->json([
            'message' => 'Dispute created for the transaction.',
            'dispute' => new DisputeResource($dispute)
        ], JsonResponse::HTTP_CREATED);
    }

    /**
     * Show me the money for testing
     */
    public function smtm(Request $request)
    {
        /**
         * @var \App\Models\Wallet
         */
        $wallet = User::whereEmail($request->email)->first()->wallets()->first();

        //$wallet->credit(2000000000, 'Show me the money', 'This is a test credit');

        return 'Done!';
    }
}
