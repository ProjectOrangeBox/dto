<?php

declare(strict_types=1);

namespace orange\dto\attributes\filters;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Converts Windows (\r\n) and old Mac (\r) line endings to Unix (\n).
 */
class NormalizeLineEndings extends DtoAttribute
{
    /**
     * Returns the normalized string or the original value when not a string.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only normalize when the input is a string.
        if (is_string($input)) {
            $output = str_replace(["\r\n", "\r"], "\n", $input);
        }

        return $output;
    }
}
