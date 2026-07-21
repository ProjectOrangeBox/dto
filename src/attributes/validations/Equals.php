<?php

declare(strict_types=1);

namespace orange\request\attributes\validations;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input equals a fixed literal value.
 */
class Equals extends RequestAttribute
{
    protected string $errorMsg = '%s must equal %s';

    /**
     * Stores the expected value and optional custom message.
     */
    public function __construct(private mixed $value, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input matches the configured value (compared as strings).
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_scalar($input) && is_scalar($this->value)) {
            $bool = (string)$input === (string)$this->value;
        }

        return $bool;
    }

    /**
     * Returns the configured expected value.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Supplies the expected value for the formatted error message.
     */
    protected function getMessageValues(): array
    {
        return [$this->value];
    }
}
