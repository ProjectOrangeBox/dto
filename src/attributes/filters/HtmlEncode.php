<?php

declare(strict_types=1);

namespace orange\request\attributes\filters;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Encodes HTML special characters so the value is safe to output.
 */
class HtmlEncode extends RequestAttribute
{
    /**
     * Returns the HTML-encoded string or the original value when not a string.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only encode when the input is a string.
        if (is_string($input)) {
            $output = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }

        return $output;
    }
}
