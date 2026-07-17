<?php

declare(strict_types=1);

namespace orange\request\attributes\filters;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Trims surrounding whitespace from string input.
 */
class Trim extends RequestAttribute
{
    /**
     * Returns the trimmed string or the original value when not a string.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only trim when the input is a string.
        if (is_string($input)) {
            $output = trim($input);
        }

        return $output;
    }
}
