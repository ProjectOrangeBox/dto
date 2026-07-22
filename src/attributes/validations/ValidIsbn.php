<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is an ISBN-10 or ISBN-13, including the checksum.
 * Hyphens and spaces are ignored.
 */
class ValidIsbn extends DtoAttribute
{
    protected string $errorMsg = '%s must contain a valid ISBN';

    /**
     * Checks whether the input is a checksum-valid ISBN-10 or ISBN-13.
     */
    public function validate(mixed $input): bool
    {
        if (!is_string($input)) {
            return false;
        }

        $isbn = strtoupper((string)preg_replace('/[\s-]+/', '', $input));

        return $this->isIsbn10($isbn) || $this->isIsbn13($isbn);
    }

    /**
     * ISBN-10: nine digits plus a check character (0-9 or X = 10); the sum
     * of each value times its weight 10..1 must divide evenly by 11.
     */
    protected function isIsbn10(string $isbn): bool
    {
        if (preg_match('/^[0-9]{9}[0-9X]$/', $isbn) !== 1) {
            return false;
        }

        $sum = 0;

        foreach (str_split($isbn) as $position => $char) {
            $sum += ($char === 'X' ? 10 : (int)$char) * (10 - $position);
        }

        return $sum % 11 === 0;
    }

    /**
     * ISBN-13: thirteen digits; the sum of each digit times alternating
     * weights 1 and 3 must divide evenly by 10.
     */
    protected function isIsbn13(string $isbn): bool
    {
        if (preg_match('/^[0-9]{13}$/', $isbn) !== 1) {
            return false;
        }

        $sum = 0;

        foreach (str_split($isbn) as $position => $char) {
            $sum += (int)$char * ($position % 2 === 0 ? 1 : 3);
        }

        return $sum % 10 === 0;
    }
}
