<?php

declare(strict_types=1);

namespace orange\dto\attributes\filters;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Collapses runs of whitespace to single spaces and trims the ends.
 */
class CollapseSpaces extends DtoAttribute
{
    /**
     * Returns the normalized string or the original value when not a string.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only normalize whitespace when the input is a string.
        if (is_string($input)) {
            $output = trim(preg_replace('/\s+/', ' ', $input));
        }

        return $output;
    }
}
