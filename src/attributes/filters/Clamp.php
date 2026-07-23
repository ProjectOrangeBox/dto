<?php

declare(strict_types=1);

namespace orange\dto\attributes\filters;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Forces numeric input into an inclusive range — the filter counterpart
 * of the Between validation.
 */
class Clamp extends DtoAttribute
{
    /**
     * Stores the inclusive minimum and maximum.
     */
    public function __construct(private readonly int|float $min, private readonly int|float $max)
    {
    }

    /**
     * Returns the value clamped to [min, max] or the original value when not numeric.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only clamp when the input is numeric.
        if (is_numeric($input)) {
            $output = min(max($input + 0, $this->min), $this->max);
        }

        return $output;
    }
}
