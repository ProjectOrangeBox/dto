<?php

declare(strict_types=1);

namespace orange\dto\attributes\filters;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Trims surrounding whitespace from string input.
 */
class Trim extends DtoAttribute
{
    /**
     * Returns the trimmed string or the original value when not a string.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only trim when the input is a string.
        if (is_string($input)) {
            $output = trim($input);
        }

        return $output;
    }
}
