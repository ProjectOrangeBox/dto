<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use DateTimeImmutable;
use orange\dto\DtoAttribute;
use Throwable;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that a date input (typically a date of birth) is at least a
 * minimum number of years in the past.
 */
class MinAge extends DtoAttribute
{
    protected string $errorMsg = '%s must be at least %s years ago';

    /**
     * Stores the minimum age in years and optional custom message.
     */
    public function __construct(private int $years, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input date is at least the configured years before today.
     */
    public function validate(mixed $input): bool
    {
        $date = $this->parseDate($input);

        return $date !== null && $date <= new DateTimeImmutable('-' . $this->years . ' years');
    }

    /**
     * Returns the configured minimum age.
     */
    public function getYears(): int
    {
        return $this->years;
    }

    /**
     * Supplies the years for the error message.
     */
    protected function getMessageValues(): array
    {
        return [$this->years];
    }

    /**
     * Parses a date string; null when the input is not a parseable date.
     */
    protected function parseDate(mixed $input): ?DateTimeImmutable
    {
        if (!is_string($input) || $input === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($input);
        } catch (Throwable) {
            return null;
        }
    }
}
