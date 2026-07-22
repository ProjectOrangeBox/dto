<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is present and not empty.
 */
class IsRequired extends DtoAttribute
{
    protected string $errorMsg = '%s is required';

    /**
     * Checks whether the input contains a non-empty value.
     */
    public function validate(mixed $input): bool
    {
        return $this->isFilled($input);
    }
}
