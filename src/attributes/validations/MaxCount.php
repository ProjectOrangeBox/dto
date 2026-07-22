<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that an array input contains at most a maximum number of elements.
 */
class MaxCount extends DtoAttribute
{
    protected string $errorMsg = '%s must contain at most %s items';

    /**
     * Stores the maximum element count and optional custom message.
     */
    public function __construct(private int $count, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input is an array with at most the configured number of elements.
     */
    public function validate(mixed $input): bool
    {
        return is_array($input) && count($input) <= $this->count;
    }

    /**
     * Returns the configured maximum count.
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Supplies the count for the error message.
     */
    protected function getMessageValues(): array
    {
        return [$this->count];
    }
}
