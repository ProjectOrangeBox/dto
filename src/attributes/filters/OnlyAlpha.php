<?php

declare(strict_types=1);

namespace orange\dto\attributes\filters;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Strips every non-letter character from string input.
 */
class OnlyAlpha extends DtoAttribute
{
    /**
     * Returns the letters-only string or the original value when not a string.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only strip characters when the input is a string.
        if (is_string($input)) {
            $output = preg_replace('/[^a-zA-Z]+/', '', $input);
        }

        return $output;
    }
}
