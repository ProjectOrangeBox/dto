<?php

declare(strict_types=1);

namespace orange\request\attributes\validations;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is a recognized PHP timezone identifier.
 */
class ValidTimezone extends RequestAttribute
{
    protected string $errorMsg = '%s must contain a valid timezone';

    /**
     * Checks whether the input is a known timezone identifier.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input)) {
            $bool = in_array($input, timezone_identifiers_list(), true);
        }

        return $bool;
    }
}
