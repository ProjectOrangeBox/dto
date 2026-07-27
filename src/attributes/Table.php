<?php

declare(strict_types=1);

namespace orange\dto\attributes;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Declares the database table and optional connection for a request property.
 */
class Table extends DtoAttribute
{
    /**
     * Stores the configured table name identifier.
     */
    public function __construct(protected string $name = '')
    {
    }

    /**
     * Returns the configured table name.
     */
    public function getName(): string
    {
        return $this->name;
    }
}
