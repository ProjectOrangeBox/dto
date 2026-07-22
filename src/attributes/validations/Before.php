<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is a date strictly before a fixed date (or 'now').
 */
class Before extends DtoAttribute
{
    protected string $errorMsg = '%s must be before %s';

    /**
     * Stores the comparison date (anything strtotime() understands, e.g. 'now') and optional custom message.
     */
    public function __construct(private string $date, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input parses to a timestamp earlier than the configured date.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input) && $input !== '') {
            $inputTime = strtotime($input);
            $compareTime = strtotime($this->date);

            $bool = $inputTime !== false && $compareTime !== false && $inputTime < $compareTime;
        }

        return $bool;
    }

    /**
     * Returns the configured comparison date.
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * Supplies the comparison date for the formatted error message.
     */
    protected function getMessageValues(): array
    {
        return [$this->date];
    }
}
