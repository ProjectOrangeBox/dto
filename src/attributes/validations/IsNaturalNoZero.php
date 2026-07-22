<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input contains a natural number greater than zero.
 */
class IsNaturalNoZero extends DtoAttribute
{
    protected string $errorMsg = '%s must contain a natural number greater than zero';

    /**
     * Checks whether the input is a natural number greater than zero.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_scalar($input)) {
            $bool = preg_match('/^[1-9][0-9]*$/', (string)$input) === 1;
        }

        return $bool;
    }
}
