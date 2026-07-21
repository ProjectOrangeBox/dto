<?php

declare(strict_types=1);

namespace orange\request\attributes\filters;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Strips every non-digit character from string input.
 */
class OnlyDigits extends RequestAttribute
{
    /**
     * Returns the digits-only string or the original value when not a string.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only strip characters when the input is a string.
        if (is_string($input)) {
            $output = preg_replace('/\D+/', '', $input);
        }

        return $output;
    }
}
