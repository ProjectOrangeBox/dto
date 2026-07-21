<?php

declare(strict_types=1);

namespace orange\request\attributes\validations;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is a plausible phone number.
 *
 * This is a loose check, not strict E.164: common formatting characters
 * (spaces, dashes, dots, parentheses) are stripped before checking for an
 * optional leading '+' followed by 7-15 digits.
 */
class ValidPhoneNumber extends RequestAttribute
{
    protected string $errorMsg = '%s must contain a valid phone number';

    /**
     * Checks whether the input, once formatting characters are stripped, looks like a phone number.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input) && $input !== '') {
            $stripped = preg_replace('/[\s\-.()]+/', '', $input);

            $bool = preg_match('/^\+?[0-9]{7,15}$/', $stripped) === 1;
        }

        return $bool;
    }
}
