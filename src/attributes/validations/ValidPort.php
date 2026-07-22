<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is a network port number (1-65535).
 */
class ValidPort extends DtoAttribute
{
    protected string $errorMsg = '%s must contain a valid port number';

    /**
     * Checks whether the input is an integer between 1 and 65535.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_int($input) || (is_string($input) && ctype_digit($input))) {
            $port = (int)$input;

            $bool = $port >= 1 && $port <= 65535;
        }

        return $bool;
    }
}
