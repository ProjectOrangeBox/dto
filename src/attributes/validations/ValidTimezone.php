<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is a recognized PHP timezone identifier.
 */
class ValidTimezone extends DtoAttribute
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
