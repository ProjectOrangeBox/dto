<?php

declare(strict_types=1);

namespace orange\dto\attributes\filters;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Strips cosmetic formatting from a phone number, keeping only digits and a
 * leading '+': '(555) 123-4567' -> '5551234567', '+1 555.123.4567' ->
 * '+15551234567'. Pairs with the ValidPhoneNumber validation.
 */
class NormalizePhone extends DtoAttribute
{
    /**
     * Returns the normalized phone string or the original value when not a string.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only normalize when the input is a string.
        if (is_string($input)) {
            $plus = str_starts_with(ltrim($input), '+') ? '+' : '';

            $output = $plus . preg_replace('/\D+/', '', $input);
        }

        return $output;
    }
}
