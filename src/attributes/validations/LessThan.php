<?php

declare(strict_types=1);

namespace orange\request\attributes\validations;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that a numeric value is less than a configured threshold.
 */
class LessThan extends RequestAttribute
{
    protected string $errorMsg = '%s must be less than %s';

    /**
     * Stores the comparison value and optional custom message.
     */
    public function __construct(protected int $length, protected string $message = '') {}

    /**
     * Checks whether the input is less than the configured value.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_numeric($input)) {
            $bool = (float)$input < $this->length;
        }

        return $bool;
    }

    /**
     * Returns the configured comparison value.
     */
    public function getValue(): int
    {
        return $this->length;
    }

    /**
     * Supplies the comparison value for the formatted error message.
     */
    protected function getMessageValues(): array
    {
        return [$this->length];
    }
}
