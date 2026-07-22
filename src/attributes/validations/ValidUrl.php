<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input contains a valid URL.
 */
class ValidUrl extends DtoAttribute
{
    protected string $errorMsg = '%s must contain a valid URL';

    /**
     * Checks whether the input is a valid URL.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input)) {
            $bool = filter_var($input, FILTER_VALIDATE_URL) !== false;
        }

        return $bool;
    }
}
