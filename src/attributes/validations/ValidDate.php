<?php

declare(strict_types=1);

namespace orange\request\attributes\validations;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is a parseable date string.
 */
class ValidDate extends RequestAttribute
{
    protected string $errorMsg = '%s must contain a valid date';

    /**
     * Checks whether the input can be interpreted as a date.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input) && $input !== '') {
            $bool = strtotime($input) !== false;
        }

        return $bool;
    }
}
