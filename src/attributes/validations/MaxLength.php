<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that a string is at most a maximum length.
 *
 * The bound is inclusive: MaxLength(64) accepts a 64 character string. It used
 * to be exclusive - rejecting one at exactly the limit - which quietly made
 * every column-width bound in the codebase one short of the column. See
 * MinLength for the same note.
 */
class MaxLength extends DtoAttribute
{
    // "at most", matching MaxCount
    protected string $errorMsg = '%s must be at most %s characters';

    /**
     * Stores the maximum length and optional custom message.
     */
    public function __construct(private readonly int $length, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input string is within the configured maximum length.
     *
     * Length is counted in bytes (strlen), not characters - which is the right
     * unit when the bound exists to fit a storage or algorithm limit, and worth
     * knowing when it exists to count what a user typed.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input)) {
            $bool = strlen($input) <= $this->length;
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
     *
     * @return list<scalar>
     */
    #[\Override]
    protected function getMessageValues(): array
    {
        return [$this->length];
    }
}
