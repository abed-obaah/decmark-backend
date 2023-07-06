<?php

namespace App\Services\Payment\Methods;

use Closure;
use Exception;
use App\Models\Airtime;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\Payment\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Walletable\Exceptions\InsufficientBalanceException;
use Walletable\Internals\Actions\ActionData;
use Walletable\Money\Money;

class WalletPaymentMethod implements PaymentMethod
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
    public function rules(Request $request): array
    {
        return [];
    }

    protected function walletFor(string $class): Wallet
    {
        if (!isset(static::$callbacks[$class])) {
            return $this->request->user()->wallets()->first();
        }

        return static::$callbacks[$class]($this->request);
    }

    public static function for(string $class, Closure $callback)
    {
        if (!class_exists($class)) {
            throw new Exception(sprintf('[%s] does not exist', $class));
        }

        static::$callbacks[$class] = $callback;
    }
}
