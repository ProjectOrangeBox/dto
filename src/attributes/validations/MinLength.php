<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that a string is at least a minimum length.
 *
 * The bound is inclusive: MinLength(4) accepts a 4 character string. It used to
 * be exclusive - requiring 5 - which read as a minimum everywhere it was
 * written and behaved as one only by accident. Every other bounded rule in this
 * package (BetweenLength, MinCount, MaxCount, MinAge) is inclusive, so this and
 * MaxLength were the two that disagreed with both their own names and their
 * neighbours.
 */
class MinLength extends DtoAttribute
{
    // "at least", matching MinCount - the old "greater than" was accurate about
    // the old off-by-one and would now be a lie
    protected string $errorMsg = '%s must be at least %s characters';

    /**
     * Stores the minimum length and optional custom message.
     */
    public function __construct(private readonly int $length, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input string is at least the configured minimum length.
     *
     * Length is counted in bytes (strlen), not characters - which is the right
     * unit when the bound exists to fit a storage or algorithm limit, and worth
     * knowing when it exists to count what a user typed.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input)) {
            $bool = strlen($input) >= $this->length;
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
    #[\Override]
    protected function getMessageValues(): array
    {
        return [$this->length];
    }
}
