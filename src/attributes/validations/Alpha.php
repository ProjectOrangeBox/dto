<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input contains only alphabetical characters.
 */
class Alpha extends DtoAttribute
{
    protected string $errorMsg = '%s may only contain alphabetical characters';

    /**
     * Checks whether the input contains only letters.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input)) {
            $bool = preg_match('/^[a-zA-Z]+$/', $input) === 1;
        }

        return $bool;
    }
}
