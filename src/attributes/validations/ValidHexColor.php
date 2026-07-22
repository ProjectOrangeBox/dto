<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is a 3- or 6-digit hex color, with an optional leading hash.
 */
class ValidHexColor extends DtoAttribute
{
    protected string $errorMsg = '%s must be a valid hex color';

    /**
     * Checks whether the input matches a hex color pattern.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input)) {
            $bool = preg_match('/^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $input) === 1;
        }

        return $bool;
    }
}
