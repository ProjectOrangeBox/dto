<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input contains a decimal number.
 */
class Decimal extends DtoAttribute
{
    protected string $errorMsg = '%s must contain a decimal number';

    /**
     * Checks whether the input matches the decimal number pattern.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_scalar($input)) {
            $bool = preg_match('/^[+-]?[0-9]+\.[0-9]+$/', (string)$input) === 1;
        }

        return $bool;
    }
}
