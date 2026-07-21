<?php

declare(strict_types=1);

namespace orange\request\attributes\validations;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that a numeric value is an exact multiple of a configured step.
 */
class MultipleOf extends RequestAttribute
{
    protected string $errorMsg = '%s must be a multiple of %s';

    /**
     * Stores the required step and optional custom message.
     */
    public function __construct(private int|float $step, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input divides evenly by the configured step.
     *
     * Uses a small epsilon tolerance so float rounding (e.g. 0.1 + 0.2) doesn't
     * produce false negatives.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_numeric($input) && (float)$this->step !== 0.0) {
            $quotient = (float)$input / (float)$this->step;

            $bool = abs($quotient - round($quotient)) < 1e-9;
        }

        return $bool;
    }

    /**
     * Returns the configured step.
     */
    public function getStep(): int|float
    {
        return $this->step;
    }

    /**
     * Supplies the step for the formatted error message.
     */
    protected function getMessageValues(): array
    {
        return [$this->step];
    }
}
