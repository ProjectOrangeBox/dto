<?php

declare(strict_types=1);

namespace orange\request\attributes\filters;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Casts request input to a string.
 */
class ToString extends RequestAttribute
{
    /**
     * Returns the string-cast value, or the original value when casting would
     * trigger a PHP conversion warning (arrays, objects without __toString).
     */
    public function filter(mixed $input): mixed
    {
        $output = $input;

        if (is_scalar($input) || $input === null) {
            $output = (string)$input;
        }

        return $output;
    }
}
