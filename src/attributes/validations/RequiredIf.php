<?php

declare(strict_types=1);

namespace orange\request\attributes\validations;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is present when another field equals a given value.
 */
class RequiredIf extends RequestAttribute
{
    protected string $errorMsg = '%s is required';

    /**
     * Stores the trigger field, its expected value, and optional custom message.
     */
    public function __construct(private string $field, private string $value, protected string $message = '') {}

    /**
     * Requires a non-empty value only when the referenced field matches the trigger value.
     */
    public function validate(mixed $input): bool
    {
        if ((string)$this->request->input($this->field) === $this->value) {
            return !empty($input);
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
