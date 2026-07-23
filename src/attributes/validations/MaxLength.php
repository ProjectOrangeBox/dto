<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that a string does not exceed a maximum length.
 */
class MaxLength extends DtoAttribute
{
    protected string $errorMsg = '%s must be less than %s characters';

    /**
     * Stores the maximum length and optional custom message.
     */
    public function __construct(private readonly int $length, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input string length is below the configured maximum.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input)) {
            $bool = strlen($input) < $this->length;
        }

        return $bool;
    }

    /**
     * Returns the configured maximum length.
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * Supplies the maximum length for the formatted error message.
     */
    #[\Override]
    protected function getMessageValues(): array
    {
        return [$this->length];
    }
}
