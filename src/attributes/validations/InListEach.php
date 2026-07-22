<?php

declare(strict_types=1);

namespace orange\dto\attributes\validations;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that every element of an array input is one of a predefined set
 * of values — whitelisting a multi-select. Compares like InList: values are
 * stringified before the strict comparison.
 */
class InListEach extends DtoAttribute
{
    protected string $errorMsg = '%s may only contain allowed values';

    /**
     * Stores the allowed values and optional custom message.
     */
    public function __construct(private array $values, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Checks whether the input is an array whose every element is in the configured list.
     */
    public function validate(mixed $input): bool
    {
        if (!is_array($input)) {
            return false;
        }

        $allowed = array_map('strval', $this->values);

        foreach ($input as $element) {
            if (!is_scalar($element) || !in_array((string)$element, $allowed, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the configured allowed values.
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
