<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is a semantic version (semver.org 2.0.0), like
 * '1.2.3', '2.0.0-rc.1', or '1.0.0+build.5'.
 */
class ValidSemver extends DtoAttribute
{
    protected string $errorMsg = '%s must contain a valid semantic version';

    // the official semver.org recommended pattern
    protected string $pattern = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)'
        . '(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?'
        . '(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';

    /**
     * Checks whether the input is a valid semantic version string.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input)) {
            $bool = preg_match($this->pattern, $input) === 1;
        }

        return $bool;
    }
}
