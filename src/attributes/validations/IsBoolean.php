<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input represents a boolean value.
 */
class IsBoolean extends DtoAttribute
{
    protected string $errorMsg = '%s must contain a boolean';

    /**
     * Checks whether the input is one of the recognized boolean values:
     * true, false, 0, 1, '0', '1', 'yes', 'no', 'on', 'off', 'true', or 'false'.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_bool($input)) {
            $bool = true;
        } elseif (is_int($input)) {
            $bool = $input === 0 || $input === 1;
        } elseif (is_string($input)) {
            $bool = in_array(strtolower($input), ['0', '1', 'yes', 'no', 'on', 'off', 'true', 'false'], true);
        }

        return $bool;
    }
}
