<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is a credit card number passing the Luhn checksum.
 */
class ValidCreditCard extends DtoAttribute
{
    protected string $errorMsg = '%s must contain a valid credit card number';

    /**
     * Checks whether the input is a 13-19 digit number that satisfies the Luhn algorithm.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input) || is_int($input)) {
            // Strip common separators before validating the digits.
            $number = preg_replace('/\D/', '', (string)$input);
            $length = strlen($number);

            if ($number !== '' && $length >= 13 && $length <= 19) {
                $bool = $this->passesLuhn($number);
            }
        }

        return $bool;
    }

    /**
     * Applies the Luhn checksum to a string of digits.
     */
    private function passesLuhn(string $number): bool
    {
        $sum = 0;
        $alternate = false;

        // Walk the digits from right to left, doubling every second digit.
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = (int)$number[$i];

            if ($alternate) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $alternate = !$alternate;
        }

        return $sum % 10 === 0;
    }
}
