<?php

declare(strict_types=1);

namespace orange\dto\attributes;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Declares the human-friendly label for a request property.
 */
class Label extends DtoAttribute
{
    /**
     * Stores the configured display label.
     */
    public function __construct(protected string $name = '')
    {
    }

    /**
     * Returns the configured display label.
     */
    public function getName(): string
    {
        return $this->name;
    }
}
