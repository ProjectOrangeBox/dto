<?php

declare(strict_types=1);

namespace orange\request\attributes\validations;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is a lower-case, hyphen-separated URL slug.
 */
class Slug extends RequestAttribute
{
    protected string $errorMsg = '%s must be a valid slug';

    /**
     * Checks whether the input is a valid slug with no leading, trailing, or repeated hyphens.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input)) {
            $bool = preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $input) === 1;
        }

        return $bool;
    }
}
