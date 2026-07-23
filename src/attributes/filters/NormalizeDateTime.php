<?php

declare(strict_types=1);

namespace orange\dto\attributes\filters;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Reformats any strtotime()-parseable date string to a canonical format
 * (default 'Y-m-d H:i:s'). Unparseable input passes through unchanged so a
 * date validation declared before this filter can still reject it.
 */
class NormalizeDateTime extends DtoAttribute
{
    /**
     * Stores the canonical output format.
     */
    public function __construct(private readonly string $format = 'Y-m-d H:i:s')
    {
    }

    /**
     * Returns the reformatted date string, or the original value when not a
     * parseable date string.
     */
    public function filter(mixed $input): mixed
    {
        // Default to returning the original value unchanged.
        $output = $input;

        // Only reformat non-empty strings strtotime() understands.
        if (is_string($input) && $input !== '') {
            $timestamp = strtotime($input);

            if ($timestamp !== false) {
                $output = date($this->format, $timestamp);
            }
        }

        return $output;
    }
}
