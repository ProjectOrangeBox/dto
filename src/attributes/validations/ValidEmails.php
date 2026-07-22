<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input contains a comma-separated list of valid email addresses.
 */
class ValidEmails extends DtoAttribute
{
    protected string $errorMsg = '%s must contain only valid email addresses';

    /**
     * Checks whether each comma-separated value is a valid email address.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input) && $input !== '') {
            $bool = true;
            $emails = array_map('trim', explode(',', $input));

            foreach ($emails as $email) {
                if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    $bool = false;

                    break;
                }
            }
        }

        return $bool;
    }
}
