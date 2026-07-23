<?php

declare(strict_types=1);

namespace orange\dto\attributes;

use Attribute;
use InvalidArgumentException;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Declares a scalar cast applied to the property's validated value in the
 * database output shapes only.
 *
 * Filters shape the domain value on the way in; DbCast shapes the storage
 * value on the way out — asColumns() and asTable() receive the cast value
 * while the typed property, asArray(), and JSON keep the domain value. A
 * null is never cast, so nullable columns stay null.
 *
 * The motivating case is a bool property whose column is an integer: the
 * property stays true/false for the application while the db shapes carry
 * 1/0 for the prepared statement.
 */
class DbCast extends DtoAttribute
{
    public const TARGETS = ['int', 'float', 'string', 'bool'];

    /**
     * Stores the cast target, rejecting anything outside TARGETS so a typo
     * fails at the class's first construction rather than silently.
     */
    public function __construct(protected string $to)
    {
        if (!in_array($to, self::TARGETS, true)) {
            throw new InvalidArgumentException(
                'DbCast target must be one of ' . implode(', ', self::TARGETS) . " — got '" . $to . "'."
            );
        }
    }

    /**
     * Returns the configured cast target.
     */
    public function getName(): string
    {
        return $this->to;
    }
}
