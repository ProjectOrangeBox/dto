<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is a ULID: 26 characters of Crockford base32,
 * with a first character of 0-7 so the timestamp fits in 48 bits.
 */
class ValidUlid extends DtoAttribute
{
    protected string $errorMsg = '%s must contain a valid ULID';

    /**
     * Checks whether the input is a valid ULID.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input)) {
            $bool = preg_match('/^[0-7][0-9A-HJKMNP-TV-Z]{25}$/i', $input) === 1;
        }

        return $bool;
    }
}
