<?php

declare(strict_types=1);

namespace orange\request\attributes\filters;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Converts an empty string into null, leaving all other values untouched.
 */
class NullIfEmpty extends RequestAttribute
{
    /**
     * Returns null when the input is an empty string, otherwise the original value.
     */
    public function filter(mixed $input): mixed
    {
        // A strict comparison avoids treating '0' or 0 as empty.
        if ($input === '') {
            return null;
        }

        return $input;
    }
}
