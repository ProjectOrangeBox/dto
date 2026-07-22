<?php

declare(strict_types=1);

namespace orange\dto\attributes\filters;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Removes all whitespace from string input — useful for values users type
 * with cosmetic spacing, like card numbers or codes.
 */
class StripSpaces extends DtoAttribute
{
    /**
     * Returns the whitespace-free string or the original value when not a string.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only strip when the input is a string.
        if (is_string($input)) {
            $output = preg_replace('/\s+/u', '', $input) ?? $input;
        }

        return $output;
    }
}
