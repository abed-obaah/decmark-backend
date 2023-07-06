<?php

namespace Tests\Unit\Payment\Gateway;

use Illuminate\Contracts\Support\Arrayable;

class ArrayableForTest implements Arrayable
{
    public function toArray()
    {
        return ['This is the test array'];
    }
}
