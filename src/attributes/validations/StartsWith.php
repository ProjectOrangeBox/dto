<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input begins with a configured substring.
 */
class StartsWith extends DtoAttribute
{
    protected string $errorMsg = '%s must start with %s';

    /**
     * Stores the required prefix and optional custom message.
     */
    public function __construct(private readonly string $needle, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input string starts with the configured prefix.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input)) {
            $bool = str_starts_with($input, $this->needle);
        }

        return $bool;
    }

    /**
     * Returns the configured prefix.
     */
    public function getNeedle(): string
    {
        return $this->needle;
    }

    /**
     * Supplies the prefix for the formatted error message.
     */
    #[\Override]
    protected function getMessageValues(): array
    {
        return [$this->needle];
    }
}
