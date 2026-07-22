<?php

declare(strict_types=1);

namespace orange\dto\attributes\filters;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Converts an empty string into null, leaving all other values untouched.
 */
class NullIfEmpty extends DtoAttribute
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
