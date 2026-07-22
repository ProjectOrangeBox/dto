<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input ends with a configured substring.
 */
class EndsWith extends DtoAttribute
{
    protected string $errorMsg = '%s must end with %s';

    /**
     * Stores the required suffix and optional custom message.
     */
    public function __construct(private string $needle, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input string ends with the configured suffix.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input)) {
            $bool = str_ends_with($input, $this->needle);
        }

        return $bool;
    }

    /**
     * Returns the configured suffix.
     */
    public function getNeedle(): string
    {
        return $this->needle;
    }

    /**
     * Supplies the suffix for the formatted error message.
     */
    protected function getMessageValues(): array
    {
        return [$this->needle];
    }
}
