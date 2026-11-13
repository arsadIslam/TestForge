<?php

namespace App\Support;

/**
 * Money is stored as integer paise. These helpers are the only place it is converted.
 */
final class Money
{
    /** 29900 → "₹299", 29950 → "₹299.50" */
    public static function format(int $paise): string
    {
        $rupees = $paise / 100;

        return '₹'.number_format($rupees, $paise % 100 === 0 ? 0 : 2);
    }

    /** "299.5" (from a form) → 29950. round() avoids 0.1-style float errors (19.99 * 100 = 1998.9999…). */
    public static function toPaise(string|float|int $rupees): int
    {
        return (int) round((float) $rupees * 100);
    }

    /** 29950 → "299.50" (for an <input> value) */
    public static function toRupees(int $paise): string
    {
        return number_format($paise / 100, 2, '.', '');
    }
}
