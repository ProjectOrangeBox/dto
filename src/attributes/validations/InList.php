<?php

declare(strict_types=1);

namespace orange\request\attributes\validations;

use Attribute;
use orange\request\RequestAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Validates that input is one of a predefined set of values.
 */
class InList extends RequestAttribute
{
  protected string $errorMsg = '%s must be one of the allowed values';

  /**
   * Stores the allowed values and optional custom message.
   */
  public function __construct(private array $values, string $message = '')
  {
    parent::__construct($message);
  }

  /**
   * Checks whether the input is present in the configured list.
   */
  public function validate(mixed $input): bool
  {
    $bool = false;

    if (is_scalar($input)) {
      $bool = in_array((string)$input, array_map('strval', $this->values), true);
    }

    return $bool;
  }

  /**
   * Returns the configured allowed values.
   */
  public function getValues(): array
  {
    return $this->values;
  }
}
