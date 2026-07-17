<?php

declare(strict_types=1);

namespace orange\request\attributes\validations;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is a syntactically valid hostname.
 */
class ValidHostname extends RequestAttribute
{
    protected string $errorMsg = '%s must contain a valid hostname';

    /**
     * Checks whether the input is a valid hostname per RFC 1034.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input) && $input !== '') {
            $bool = filter_var($input, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
        }

        return $bool;
    }
}
