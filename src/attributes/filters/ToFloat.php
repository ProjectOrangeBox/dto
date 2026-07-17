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
     * Returns the float-cast value.
     */
    public function filter(mixed $input): mixed
    {
        return (float)$input;
    }
}
