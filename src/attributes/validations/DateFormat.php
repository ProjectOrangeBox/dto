<?php

declare(strict_types=1);

namespace orange\request\attributes\validations;

use Attribute;
use DateTimeImmutable;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input matches an exact date format.
 */
class DateFormat extends RequestAttribute
{
    protected string $errorMsg = '%s must be a valid date in the format %s';

    /**
     * Stores the required date format and optional custom message.
     */
    public function __construct(private string $format = 'Y-m-d', protected string $message = '') {}

    /**
     * Checks whether the input parses to a real date and round-trips to the same string.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input)) {
            $date = DateTimeImmutable::createFromFormat($this->format, $input);

            // Re-formatting guards against overflow dates such as 2023-13-45.
            $bool = $date !== false && $date->format($this->format) === $input;
        }

        return $bool;
    }

    /**
     * Returns the configured date format.
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Supplies the format for the formatted error message.
     */
    protected function getMessageValues(): array
    {
        return [$this->format];
    }
}
