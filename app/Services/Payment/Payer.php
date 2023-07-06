<?php

namespace App\Services\Payment;

use App\Services\Payment\PaymentManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\Payment\Methods\PaymentMethod method(string $name, $method = null)
 * @method static \Illuminate\Validation\Rules\In ruleIn()
 */
class Payer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return PaymentManager::class;
    }
}
