<?php

declare(strict_types=1);

namespace orange\request\attributes\filters;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Converts string input to upper case.
 */
class ToUpper extends RequestAttribute
{
    /**
     * Returns the upper-cased string or the original value when not a string.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only change case when the input is a string.
        if (is_string($input)) {
            $output = mb_strtoupper($input);
        }

        return $output;
    }
}
