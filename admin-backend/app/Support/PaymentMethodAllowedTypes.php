<?php

namespace App\Support;

/**
 * Allowed `payment_methods.payment_type` values (varchar). Keeps validation and migrations aligned.
 */
class PaymentMethodAllowedTypes
{
    public const ALLOWED = [
        'Card',
        'Mobile',
        'Digital Wallet',
        'Cash',
        'BNPL',
        'Cryptocurrencies',
        'Wire',
        'Others',
    ];

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return self::ALLOWED;
    }

    /**
     * Normalize DB values after INT→VARCHAR migrate: legacy integers or unknown strings → allowed value.
     *
     * @param  mixed  $value
     */
    public static function migrateFromLegacy($value): string
    {
        if ($value === null || $value === '') {
            return 'Others';
        }

        if (is_string($value)) {
            $trim = trim($value);
            if (in_array($trim, self::ALLOWED, true)) {
                return $trim;
            }

            return self::migrateFromLegacyNumericString($trim);
        }

        if (is_numeric($value)) {
            switch ((int) $value) {
                case 1:
                    return 'Cash';

                case 2:
                    return 'Card';

                case 3:
                    return 'Digital Wallet';

                default:
                    return 'Others';
            }
        }

        return 'Others';
    }

    /**
     * @param  non-empty-string  $digits
     */
    private static function migrateFromLegacyNumericString(string $digits): string
    {
        if (! ctype_digit($digits)) {
            return 'Others';
        }

        switch ((int) $digits) {
            case 1:
                return 'Cash';

            case 2:
                return 'Card';

            case 3:
                return 'Digital Wallet';

            default:
                return 'Others';
        }
    }
}
