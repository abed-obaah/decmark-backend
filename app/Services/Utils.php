<?php

namespace App\Services;

class Utils
{
    public static function random(
        int $length = 4,
        bool $alpha = true,
        bool $numeric = false,
        bool $uppercase = false
    ) {
        $char = $alpha ? 'abcdefghijklmnopqrstuvwxyz' : '';
        $char .= $numeric ? '0123456789' : '';
        $char .= $uppercase ? 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' : '';
        $gen = '';

        for ($i = 0; $i < $length; $i++) {
            $gen .= $char[mt_rand(0, (strlen($char) - 1))];
        }

        return $gen;
    }
}
