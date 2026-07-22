<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is present when another field has a value.
 */
class RequiredWith extends DtoAttribute
{
    protected string $errorMsg = '%s is required';

    /**
     * Stores the companion field name and optional custom message.
     */
    public function __construct(private string $field, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Requires a non-empty value only when the companion field is non-empty.
     */
    public function validate(mixed $input): bool
    {
        if ($this->isFilled($this->dto->input($this->field))) {
            return $this->isFilled($input);
        }

        return true;
    }

    /**
     * Returns the companion field name.
     */
    public function getField(): string
    {
        return $this->field;
    }
}
