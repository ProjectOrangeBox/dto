<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that a string has an exact length.
 */
class ExactLength extends DtoAttribute
{
    protected string $errorMsg = '%s must be exactly %s characters';

    /**
     * Stores the required length and optional custom message.
     */
    public function __construct(private readonly int $length, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input string length matches the configured value.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input)) {
            $bool = strlen($input) === $this->length;
        }

        return $bool;
    }

    /**
     * Returns the configured exact length.
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * Supplies the configured length for the formatted error message.
     */
    #[\Override]
    protected function getMessageValues(): array
    {
        return [$this->length];
    }
}
