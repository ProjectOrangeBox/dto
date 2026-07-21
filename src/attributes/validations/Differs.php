<?php

declare(strict_types=1);

namespace orange\request\attributes\validations;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input differs from another request field.
 */
class Differs extends RequestAttribute
{
    protected string $errorMsg = '%s must differ from %s';

    /**
     * Stores the comparison field name and optional custom message.
     */
    public function __construct(private string $field, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input differs from the referenced field value.
     */
    public function validate(mixed $input): bool
    {
        return $input !== $this->request->input($this->field);
    }

    /**
     * Returns the referenced field name.
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Supplies the field name used in the formatted error message.
     */
    protected function getMessageValues(): array
    {
        return [$this->field];
    }
}
