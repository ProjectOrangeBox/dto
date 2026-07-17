<?php

declare(strict_types=1);

namespace orange\request\attributes\validations;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input contains a well-formed JSON string.
 */
class ValidJson extends RequestAttribute
{
    protected string $errorMsg = '%s must contain valid JSON';

    /**
     * Checks whether the input is a valid JSON string.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input) && $input !== '') {
            $bool = json_validate($input);
        }

        return $bool;
    }
}
