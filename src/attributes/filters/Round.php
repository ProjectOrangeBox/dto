<?php

declare(strict_types=1);

namespace orange\dto\attributes\filters;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Rounds numeric input to a configured number of decimal places.
 */
class Round extends DtoAttribute
{
    /**
     * Stores the number of decimal places to round to.
     */
    public function __construct(private int $precision = 0) {}

    /**
     * Returns the rounded float or the original value when not numeric.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only round when the input is numeric.
        if (is_numeric($input)) {
            $output = round((float)$input, $this->precision);
        }

        return $output;
    }

    /**
     * Returns the configured decimal precision.
     */
    public function getPrecision(): int
    {
        return $this->precision;
    }
}
