<?php

namespace App\Services\Payment\Methods;

use Illuminate\Http\Request;

interface PaymentMethod
{
    /**
     * Request rules
     *
     * @return array
     */
    public function rules(Request $request): array;
}
