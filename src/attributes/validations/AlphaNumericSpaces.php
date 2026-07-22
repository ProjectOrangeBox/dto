<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input contains only letters, numbers, and spaces.
 */
class AlphaNumericSpaces extends DtoAttribute
{
    protected string $errorMsg = '%s may only contain alpha-numeric characters and spaces';

    /**
     * Checks whether the input is alpha-numeric with spaces allowed.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input)) {
            $bool = preg_match('/^[a-zA-Z0-9 ]+$/', $input) === 1;
        }

        return $bool;
    }
}
