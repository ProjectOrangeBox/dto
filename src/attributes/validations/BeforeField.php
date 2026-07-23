<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is a date strictly before another request field's date.
 */
class BeforeField extends DtoAttribute
{
    protected string $errorMsg = '%s must be before %s';

    /**
     * Stores the comparison field name and optional custom message.
     */
    public function __construct(private readonly string $field, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input parses to a timestamp earlier than the referenced field's value.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input) && $input !== '') {
            $other = $this->dto->input($this->field);

            if (is_string($other) && $other !== '') {
                $inputTime = strtotime($input);
                $otherTime = strtotime($other);

                $bool = $inputTime !== false && $otherTime !== false && $inputTime < $otherTime;
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
    #[\Override]
    protected function getMessageValues(): array
    {
        return [$this->field];
    }
}
