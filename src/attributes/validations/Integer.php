<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input contains an integer.
 */
class Integer extends DtoAttribute
{
    protected string $errorMsg = '%s must contain an integer';

    /**
     * Checks whether the input matches the integer pattern.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_scalar($input)) {
            $bool = preg_match('/^[+-]?[0-9]+$/', (string)$input) === 1;
        }

        return $bool;
    }
}
