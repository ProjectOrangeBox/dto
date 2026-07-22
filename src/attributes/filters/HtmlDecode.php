<?php

declare(strict_types=1);

namespace orange\dto\attributes\filters;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Decodes HTML special character entities — the inverse of HtmlEncode.
 */
class HtmlDecode extends DtoAttribute
{
    /**
     * Returns the decoded string or the original value when not a string.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only decode when the input is a string.
        if (is_string($input)) {
            $output = htmlspecialchars_decode($input, ENT_QUOTES);
        }

        return $output;
    }
}
