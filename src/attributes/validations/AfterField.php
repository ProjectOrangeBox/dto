<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is a date strictly after another request field's date.
 */
class AfterField extends DtoAttribute
{
    protected string $errorMsg = '%s must be after %s';

    /**
     * Stores the comparison field name and optional custom message.
     */
    public function __construct(private string $field, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input parses to a timestamp later than the referenced field's value.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input) && $input !== '') {
            $other = $this->dto->input($this->field);

            if (is_string($other) && $other !== '') {
                $inputTime = strtotime($input);
                $otherTime = strtotime($other);

                $bool = $inputTime !== false && $otherTime !== false && $inputTime > $otherTime;
            }
        }

        return $bool;
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
    protected function getMessageValues(): array
    {
        return [$this->field];
    }
}
