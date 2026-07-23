<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that an array input contains at least a minimum number of elements.
 */
class MinCount extends DtoAttribute
{
    protected string $errorMsg = '%s must contain at least %s items';

    /**
     * Stores the minimum element count and optional custom message.
     */
    public function __construct(private readonly int $count, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input is an array with at least the configured number of elements.
     */
    public function validate(mixed $input): bool
    {
        return is_array($input) && count($input) >= $this->count;
    }

    /**
     * Returns the configured minimum count.
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Supplies the count for the error message.
     */
    #[\Override]
    protected function getMessageValues(): array
    {
        return [$this->count];
    }
}
