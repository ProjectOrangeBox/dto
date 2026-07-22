<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input contains a numeric value.
 */
class Numeric extends DtoAttribute
{
    protected string $errorMsg = '%s must contain only numbers';

    /**
     * Checks whether the input is numeric.
     */
    public function validate(mixed $input): bool
    {
        return is_scalar($input) && is_numeric((string)$input);
    }
}
