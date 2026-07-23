<?php

declare(strict_types=1);

namespace orange\dto\attributes\filters;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Left-pads string or integer input to a fixed length — zero-padding codes
 * like '42' -> '00042'. Values already at or beyond the length are unchanged.
 */
class Pad extends DtoAttribute
{
    /**
     * Stores the target length and the padding string.
     */
    public function __construct(private readonly int $length, private readonly string $padString = '0')
    {
    }

    /**
     * Returns the padded string or the original value when not a string or integer.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only pad strings and integers — anything else passes through.
        if (is_string($input) || is_int($input)) {
            $output = str_pad((string)$input, $this->length, $this->padString, STR_PAD_LEFT);
        }

        return $output;
    }
}
