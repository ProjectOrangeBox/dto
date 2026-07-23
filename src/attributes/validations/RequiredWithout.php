<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is present when another field is empty.
 */
class RequiredWithout extends DtoAttribute
{
    protected string $errorMsg = '%s is required';
    protected bool $validateWhenAbsent = true;

    /**
     * Stores the companion field and optional custom message.
     */
    public function __construct(private readonly string $field, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Requires a non-empty value only when the referenced field is empty.
     */
    public function validate(mixed $input): bool
    {
        if ($this->isFilled($this->dto->input($this->field))) {
            return true;
        }

        return $this->isFilled($input);
    }

    /**
     * Returns the referenced companion field name.
     */
    public function getField(): string
    {
        return $this->field;
    }
}
