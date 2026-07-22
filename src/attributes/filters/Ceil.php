<?php

declare(strict_types=1);

namespace orange\dto\attributes\filters;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Rounds numeric input up to the next whole number.
 */
class Ceil extends DtoAttribute
{
    /**
     * Returns the rounded-up float or the original value when not numeric.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only round when the input is numeric.
        if (is_numeric($input)) {
            $output = ceil((float)$input);
        }

        return $output;
    }
}
