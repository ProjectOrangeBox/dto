<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is present except when another field equals a given value.
 */
class RequiredUnless extends DtoAttribute
{
    protected string $errorMsg = '%s is required';
    protected bool $validateWhenAbsent = true;

    /**
     * Stores the trigger field, its exempting value, and optional custom message.
     */
    public function __construct(private readonly string $field, private readonly string $value, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Requires a non-empty value unless the referenced field matches the exempting value.
     */
    public function validate(mixed $input): bool
    {
        if ((string)$this->dto->input($this->field) === $this->value) {
            return true;
        }

        return $this->isFilled($input);
    }

    /**
     * Returns the referenced trigger field name.
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Returns the exempting value.
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
