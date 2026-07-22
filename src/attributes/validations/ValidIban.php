<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is an IBAN: country code, two check digits, then up
 * to 30 alphanumerics, verified with the ISO 7064 mod-97 checksum. Spaces
 * are ignored and letter case does not matter.
 */
class ValidIban extends DtoAttribute
{
    protected string $errorMsg = '%s must contain a valid IBAN';

    /**
     * Checks whether the input passes the IBAN structure and checksum.
     */
    public function validate(mixed $input): bool
    {
        if (!is_string($input)) {
            return false;
        }

        $iban = strtoupper((string)preg_replace('/\s+/', '', $input));

        if (preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/', $iban) !== 1) {
            return false;
        }

        // move the country code and check digits to the end, then replace
        // letters with their numeric values (A=10 ... Z=35)
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);
        $digits = '';

        foreach (str_split($rearranged) as $char) {
            $digits .= ctype_alpha($char) ? (string)(ord($char) - 55) : $char;
        }

        // piecewise mod-97 keeps the running value inside integer range
        $remainder = 0;

        foreach (str_split($digits, 7) as $chunk) {
            $remainder = (int)(($remainder . $chunk) % 97);
        }

        return $remainder === 1;
    }
}
