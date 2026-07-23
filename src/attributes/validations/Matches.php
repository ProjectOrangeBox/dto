<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input matches another request field.
 */
class Matches extends DtoAttribute
{
    protected string $errorMsg = '%s must match %s';

    /**
     * Stores the comparison field name and optional custom message.
     */
    public function __construct(private readonly string $field, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input matches the referenced field value.
     */
    public function validate(mixed $input): bool
    {
        return $input === $this->dto->input($this->field);
    }

    /**
     * Returns the referenced field name.
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Supplies the field name for the formatted error message.
     */
    #[\Override]
    protected function getMessageValues(): array
    {
        return [$this->field];
    }
}
