<?php

declare(strict_types=1);

namespace orange\request\attributes\validations;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that a string exceeds a minimum length.
 */
class MinLength extends RequestAttribute
{
    protected string $errorMsg = '%s must be greater than %s characters';

    /**
     * Stores the minimum length and optional custom message.
     */
    public function __construct(private int $length, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input string length is above the configured minimum.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input)) {
            $bool = strlen($input) > $this->length;
        }

        return $bool;
    }

    /**
     * Returns the configured minimum length.
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * Supplies the minimum length for the formatted error message.
     */
    protected function getMessageValues(): array
    {
        return [$this->length];
    }
}
