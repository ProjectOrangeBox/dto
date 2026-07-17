<?php

declare(strict_types=1);

namespace orange\request\attributes\validations;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that a numeric value falls within an inclusive range.
 */
class Between extends RequestAttribute
{
    protected string $errorMsg = '%s must be between %s and %s';

    /**
     * Stores the inclusive bounds and optional custom message.
     */
    public function __construct(private int|float $min, private int|float $max, protected string $message = '') {}

    /**
     * Checks whether the input is between the configured minimum and maximum.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_numeric($input)) {
            $value = (float)$input;

            $bool = $value >= $this->min && $value <= $this->max;
        }

        return $bool;
    }

    /**
     * Returns the configured minimum value.
     */
    public function getMin(): int|float
    {
        return $this->min;
    }

    /**
     * Returns the configured maximum value.
     */
    public function getMax(): int|float
    {
        return $this->max;
    }

    /**
     * Supplies the bounds for the formatted error message.
     */
    protected function getMessageValues(): array
    {
        return [$this->min, $this->max];
    }
}
