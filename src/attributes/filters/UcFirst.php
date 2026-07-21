<?php

declare(strict_types=1);

namespace orange\request\attributes\filters;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Converts the first letter of string input to upper case, leaving the rest untouched.
 */
class UcFirst extends RequestAttribute
{
    /**
     * Returns the string with its first letter upper-cased, or the original value when not a string.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only change case when the input is a string.
        if (is_string($input)) {
            $output = mb_strtoupper(mb_substr($input, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($input, 1, null, 'UTF-8');
        }

        return $output;
    }
}
