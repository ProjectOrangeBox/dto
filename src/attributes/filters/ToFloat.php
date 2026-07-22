<?php

declare(strict_types=1);

namespace orange\dto\attributes\filters;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Casts request input to a float.
 */
class ToFloat extends DtoAttribute
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
