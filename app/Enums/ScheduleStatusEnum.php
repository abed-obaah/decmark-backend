<?php

namespace App\Enums;

abstract class ScheduleStatusEnum extends BaseEnum
{
    public const BOOKED = 'BOOKED';

    public const OPENED = 'OPENED';

    public const ONGOING = 'ONGOING';

    public const SETTELED = 'SETTELED';

    public const DECLINED = 'DECLINED';
}
