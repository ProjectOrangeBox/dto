<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that a string length falls within an inclusive range.
 */
class BetweenLength extends DtoAttribute
{
    protected string $errorMsg = '%s must be between %s and %s characters';

    /**
     * Stores the inclusive length bounds and optional custom message.
     */
    public function __construct(private readonly int $min, private readonly int $max, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input length is between the configured minimum and maximum.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input)) {
            $length = strlen($input);

            $bool = $length >= $this->min && $length <= $this->max;
        }

        return $bool;
    }

    /**
     * Returns the configured minimum length.
     */
    public function getMin(): int
    {
        return $this->min;
    }

    /**
     * Returns the configured maximum length.
     */
    public function getMax(): int
    {
        return $this->max;
    }

    /**
     * Supplies the bounds for the formatted error message.
     */
    #[\Override]
    protected function getMessageValues(): array
    {
        return [$this->min, $this->max];
    }
}
