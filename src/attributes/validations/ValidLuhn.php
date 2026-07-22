<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input passes the Luhn mod-10 checksum — the algorithm
 * behind card numbers, IMEIs, and many account identifiers. Spaces and
 * dashes are ignored. Use ValidCreditCard when the value is specifically
 * a payment card number.
 */
class ValidLuhn extends DtoAttribute
{
    protected string $errorMsg = '%s must contain a valid number';

    /**
     * Checks whether the input digits pass the Luhn checksum.
     */
    public function validate(mixed $input): bool
    {
        if (!is_string($input) && !is_int($input)) {
            return false;
        }

        $number = (string)preg_replace('/[\s-]+/', '', (string)$input);

        if (preg_match('/^[0-9]{2,}$/', $number) !== 1) {
            return false;
        }

        $sum = 0;
        $double = false;

        // walk right to left, doubling every second digit
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = (int)$number[$i];

            if ($double) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $double = !$double;
        }

        return $sum % 10 === 0;
    }
}
