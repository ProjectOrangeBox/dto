<?php

declare(strict_types=1);

namespace orange\request\attributes\validations;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input represents an accepted/checked value (e.g. a "terms of service" checkbox).
 */
class Accepted extends RequestAttribute
{
    protected string $errorMsg = '%s must be accepted';

    /**
     * Checks whether the input is one of the recognized truthy values: true, 1, '1', 'yes', or 'on'.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_bool($input)) {
            $bool = $input === true;
        } elseif (is_int($input)) {
            $bool = $input === 1;
        } elseif (is_string($input)) {
            $bool = in_array(strtolower($input), ['1', 'yes', 'on', 'true'], true);
        }

        return $bool;
    }
}
