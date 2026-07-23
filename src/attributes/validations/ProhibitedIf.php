<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is empty when another field equals a given value.
 */
class ProhibitedIf extends DtoAttribute
{
    protected string $errorMsg = '%s must be empty';

    /**
     * Stores the trigger field, its prohibiting value, and optional custom message.
     */
    public function __construct(private readonly string $field, private readonly string $value, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Rejects a non-empty value when the referenced field matches the prohibiting value.
     */
    public function validate(mixed $input): bool
    {
        if ((string)$this->dto->input($this->field) === $this->value) {
            return !$this->isFilled($input);
        }

        return true;
    }

    /**
     * Returns the referenced trigger field name.
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Returns the prohibiting value.
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
