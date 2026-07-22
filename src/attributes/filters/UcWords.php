<?php

declare(strict_types=1);

namespace orange\dto\attributes\filters;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Converts the first letter of each word in string input to upper case.
 */
class UcWords extends DtoAttribute
{
    /**
     * Returns the title-cased string or the original value when not a string.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only change case when the input is a string.
        if (is_string($input)) {
            $output = mb_convert_case($input, MB_CASE_TITLE, 'UTF-8');
        }

        return $output;
    }
}
