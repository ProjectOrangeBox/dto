<?php

declare(strict_types=1);

namespace orange\request\attributes\filters;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Converts string input into a lower-case, hyphen-separated slug.
 */
class Slugify extends RequestAttribute
{
    /**
     * Returns the slugified string or the original value when not a string.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only slugify when the input is a string.
        if (is_string($input)) {
            $lower = mb_strtolower($input, 'UTF-8');

            // Replace anything that isn't a letter or digit with a hyphen, then trim the ends.
            $output = trim(preg_replace('/[^a-z0-9]+/', '-', $lower), '-');
        }

        return $output;
    }
}
