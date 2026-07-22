<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is present when another field equals a given value.
 */
class RequiredIf extends DtoAttribute
{
    protected string $errorMsg = '%s is required';

    /**
     * Stores the trigger field, its expected value, and optional custom message.
     */
    public function __construct(private string $field, private string $value, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Requires a non-empty value only when the referenced field matches the trigger value.
     */
    public function validate(mixed $input): bool
    {
        if ((string)$this->dto->input($this->field) === $this->value) {
            return $this->isFilled($input);
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
     * Returns the trigger value.
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
