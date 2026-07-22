<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input does not contain a given substring.
 */
class NotContains extends DtoAttribute
{
    protected string $errorMsg = '%s must not contain %s';

    /**
     * Stores the forbidden substring and optional custom message.
     */
    public function __construct(private string $needle, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input does not contain the configured substring.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input)) {
            $bool = !str_contains($input, $this->needle);
        }

        return $bool;
    }

    /**
     * Returns the forbidden substring.
     */
    public function getNeedle(): string
    {
        return $this->needle;
    }

    /**
     * Supplies the substring for the error message.
     */
    protected function getMessageValues(): array
    {
        return [$this->needle];
    }
}
