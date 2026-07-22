<?php

declare(strict_types=1);

namespace orange\dto\attributes\filters;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Converts numeric input to its absolute value.
 */
class Abs extends DtoAttribute
{
    /**
     * Returns the absolute value or the original value when not numeric.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only convert when the input is numeric.
        if (is_numeric($input)) {
            $output = abs($input + 0);
        }

        return $output;
    }
}
