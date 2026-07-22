<?php

declare(strict_types=1);

namespace orange\dto\attributes\filters;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Removes HTML and PHP tags from string input.
 */
class StripTags extends DtoAttribute
{
    /**
     * Returns the tag-stripped string or the original value when not a string.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only strip tags when the input is a string.
        if (is_string($input)) {
            $output = strip_tags($input);
        }

        return $output;
    }
}
