<?php

declare(strict_types=1);

namespace orange\request\attributes\filters;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Casts request input to a float.
 */
class ToFloat extends RequestAttribute
{
    /**
     * Returns the float-cast value, or the original value when casting would
     * trigger a PHP conversion warning (arrays, objects).
     */
    public function filter(mixed $input): mixed
    {
        $output = $input;

        if (is_scalar($input) || $input === null) {
            $output = (float)$input;
        }

        return $output;
    }
}
