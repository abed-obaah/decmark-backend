<?php

namespace App\Services\Payment\Gateway;

use App\Services\Payment\Gateway\Drivers\DriverInterface;

interface HasGateway
{
    /**
     * Get Gateway
     *
     * @return DriverInterface
     */
    public function getGateway(): DriverInterface;
}
