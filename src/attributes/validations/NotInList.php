<?php

declare(strict_types=1);

namespace orange\request\attributes\validations;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is not one of a disallowed set of values.
 */
class NotInList extends RequestAttribute
{
    protected string $errorMsg = '%s must not be one of the disallowed values';

    /**
     * Stores the disallowed values and optional custom message.
     */
    public function __construct(private array $values, protected string $message = '') {}

    /**
     * Checks whether the input is absent from the configured list.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_scalar($input)) {
            $bool = !in_array((string)$input, array_map('strval', $this->values), true);
        }

        return $bool;
    }

    /**
     * Returns the configured disallowed values.
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
