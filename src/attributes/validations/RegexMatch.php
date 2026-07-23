<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input matches a custom regular expression.
 */
class RegexMatch extends DtoAttribute
{
    protected string $errorMsg = '%s is not in the correct format';

    /**
     * Stores the regex pattern and optional custom message.
     */
    public function __construct(private readonly string $pattern, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input matches the configured regex pattern.
     */
    public function validate(mixed $input): bool
    {
        $bool = false;

        if (is_string($input)) {
            $bool = preg_match($this->pattern, $input) === 1;
        }

        return $bool;
    }

    /**
     * Returns the configured regex pattern.
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }
}
