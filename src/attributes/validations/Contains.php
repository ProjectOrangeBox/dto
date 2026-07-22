<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input contains a configured substring.
 */
class Contains extends DtoAttribute
{
    protected string $errorMsg = '%s must contain %s';

    /**
     * Stores the required substring and optional custom message.
     */
    public function __construct(private string $needle, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input string contains the configured substring.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input)) {
            $bool = str_contains($input, $this->needle);
        }

        return $bool;
    }

    /**
     * Returns the configured substring.
     */
    public function getNeedle(): string
    {
        return $this->needle;
    }

    /**
     * Supplies the substring for the formatted error message.
     */
    protected function getMessageValues(): array
    {
        return [$this->needle];
    }
}
