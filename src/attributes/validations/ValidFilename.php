<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is a safe bare filename: no directory separators or
 * traversal, no null bytes or control characters, not '.' or '..', and at
 * most 255 bytes.
 */
class ValidFilename extends DtoAttribute
{
    protected string $errorMsg = '%s must contain a valid filename';

    /**
     * Checks whether the input is a safe bare filename.
     */
    public function validate(mixed $input): bool
    {
        if (!is_string($input) || $input === '' || strlen($input) > 255) {
            return false;
        }

        if ($input === '.' || $input === '..') {
            return false;
        }

        // no path separators, null bytes, or control characters
        return preg_match('/[\/\\\\\x00-\x1F\x7F]/', $input) === 0;
    }
}
