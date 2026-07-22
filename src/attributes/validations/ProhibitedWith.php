<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is empty when another field is filled — the two
 * fields are mutually exclusive.
 */
class ProhibitedWith extends DtoAttribute
{
    protected string $errorMsg = '%s must be empty';

    /**
     * Stores the companion field and optional custom message.
     */
    public function __construct(private string $field, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Rejects a non-empty value when the referenced field is also filled.
     */
    public function validate(mixed $input): bool
    {
        if ($this->isFilled($this->dto->input($this->field))) {
            return !$this->isFilled($input);
        }

        return true;
    }

    /**
     * Returns the referenced companion field name.
     */
    public function getField(): string
    {
        return $this->field;
    }
}
