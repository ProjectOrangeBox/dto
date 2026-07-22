<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input contains a well-formed JSON string.
 */
class ValidJson extends DtoAttribute
{
    protected string $errorMsg = '%s must contain valid JSON';

    /**
     * Checks whether the input is a valid JSON string.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input) && $input !== '') {
            $bool = json_validate($input);
        }

        return $bool;
    }
}
