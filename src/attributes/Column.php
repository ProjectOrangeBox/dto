<?php

declare(strict_types=1);

namespace orange\dto\attributes;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Declares the database column name for a request property.
 */
class Column extends DtoAttribute
{
    /**
     * Stores the configured column name.
     */
    public function __construct(protected string $name = '')
    {
    }

    /**
     * Returns the configured column name.
     */
    public function getName(): string
    {
        return $this->name;
    }
}
