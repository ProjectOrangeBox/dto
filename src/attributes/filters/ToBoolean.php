<?php

declare(strict_types=1);

namespace orange\dto\attributes\filters;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Normalizes request input into a boolean value.
 */
class ToBoolean extends DtoAttribute
{
    /**
     * Converts booleans, strings, and integers into a normalized boolean result.
     */
    public function filter(mixed $input): mixed
    {
        // default to false
        $output = false;

        // Handle boolean values directly.
        if (is_bool($input)) {
            $output = $input;
        }

        // Handle string representations of boolean values — the same truthy
        // set IsBoolean accepts. '1' matters: PDO with emulated prepares
        // returns integer columns as strings, so a stored 1 arrives as '1'.
        if (is_string($input)) {
            // Normalize the string to lowercase for comparison.
            $input = strtolower($input);

            // Check for common truthy string values.
            if (in_array($input, ['true', 'yes', 'on', '1'], true)) {
                $output = true;
            }
        }

        // Handle integer values
        if (is_int($input)) {
            // If the input is not zero, consider it as true.
            if ($input !== 0) {
                $output = true;
            }
        }

        // Return the normalized boolean result.
        return $output;
    }
}
