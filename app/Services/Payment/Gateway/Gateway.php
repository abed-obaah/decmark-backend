<?php

namespace App\Services\Payment\Gateway;

use App\Services\Payment\Gateway\GatewayManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\Payment\Gateway\Drivers\DriverInterface driver(string $name, $driver = null)
 * @method static \App\Services\Payment\Gateway\LabelInterface label(string $name, $label = null)
 * @method static \Illuminate\Validation\Rules\In ruleIn()
 * @method static void processLabel(LabelInterface $label, Transaction $transaction)
 * @method static void processRefund(LabelInterface $label, Refund $refund)
 */
class Gateway extends Facade
{
    protected static function getFacadeAccessor()
    {
        return GatewayManager::class;
    }
}
