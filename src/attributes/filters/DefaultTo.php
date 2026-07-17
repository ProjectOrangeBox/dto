<?php

declare(strict_types=1);

namespace orange\request\attributes\filters;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Substitutes a default value when input is missing (null or an empty string).
 */
class DefaultTo extends RequestAttribute
{
    /**
     * Stores the fallback value to use for empty input.
     */
    public function __construct(private mixed $default = null) {}

    /**
     * Returns the configured default when the input is null or an empty string.
     */
    public function filter(mixed $input): mixed
    {
        // A strict comparison avoids treating '0' or 0 as empty.
        if ($input === null || $input === '') {
            return $this->default;
        }

        return $input;
    }

    /**
     * Returns the configured default value.
     */
    public function getDefault(): mixed
    {
        return $this->default;
    }
}
