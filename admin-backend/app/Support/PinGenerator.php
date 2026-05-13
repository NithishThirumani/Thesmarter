<?php

namespace App\Support;

class PinGenerator
{
    /**
     * Strict 4-digit numeric PIN for Bizwy Executive / Super User flows.
     */
    public static function generateFourDigit(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a numeric PIN with length between 4 and 6 digits (inclusive).
     */
    public static function generate(): string
    {
        $length = random_int(4, 6);
        $min = (int) pow(10, $length - 1);
        $max = (int) pow(10, $length) - 1;

        return (string) random_int($min, $max);
    }
}
