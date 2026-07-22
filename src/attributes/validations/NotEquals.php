<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input does not equal a fixed literal value.
 */
class NotEquals extends DtoAttribute
{
    protected string $errorMsg = '%s must not equal %s';

    /**
     * Stores the disallowed value and optional custom message.
     */
    public function __construct(private mixed $value, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input differs from the configured value (compared as strings).
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_scalar($input) && is_scalar($this->value)) {
            $bool = (string)$input !== (string)$this->value;
        }

        return $bool;
    }

    /**
     * Returns the configured disallowed value.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Supplies the disallowed value for the formatted error message.
     */
    protected function getMessageValues(): array
    {
        return [$this->value];
    }
}
