<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is an array — multi-select and checkbox-group inputs.
 */
class IsArray extends DtoAttribute
{
    protected string $errorMsg = '%s must be an array';

    /**
     * Checks whether the input is an array.
     */
    public function validate(mixed $input): bool
    {
        return is_array($input);
    }
}
