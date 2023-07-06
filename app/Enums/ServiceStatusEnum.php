<?php

namespace App\Enums;

abstract class ServiceStatusEnum extends BaseEnum
{
    public const PENDING = 'PENDING';

    public const ONGOING = 'ONGOING';

    public const COMPLETED = 'COMPLETED';
}
