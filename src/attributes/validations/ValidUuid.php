<?php

declare(strict_types=1);

namespace orange\request\attributes\validations;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is a well-formed RFC 4122 UUID.
 */
class ValidUuid extends RequestAttribute
{
    protected string $errorMsg = '%s must contain a valid UUID';

    /**
     * Checks whether the input matches the UUID pattern for versions 1-8.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input)) {
            $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

            $bool = preg_match($pattern, $input) === 1;
        }

        return $bool;
    }
}
