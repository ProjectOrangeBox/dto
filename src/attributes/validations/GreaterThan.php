<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that a numeric value is greater than a configured threshold.
 */
class GreaterThan extends DtoAttribute
{
    protected string $errorMsg = '%s must be greater than %s';

    /**
     * Stores the comparison value and optional custom message.
     */
    public function __construct(protected int|float $value, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input is greater than the configured value.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_numeric($input)) {
            $bool = (float)$input > $this->value;
        }

        return $bool;
    }

    /**
     * Returns the configured comparison value.
     */
    public function getValue(): int|float
    {
        return $this->value;
    }

    /**
     * Supplies the comparison value for the formatted error message.
     */
    #[\Override]
    protected function getMessageValues(): array
    {
        return [$this->value];
    }
}
