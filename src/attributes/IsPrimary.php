<?php

declare(strict_types=1);

namespace orange\dto\attributes;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Tags the property that holds the record's primary key.
 *
 * A pure marker — it performs no validation or filtering. The Dto records the
 * tagged property's column name (falling back to its resolved field name when
 * no #[Column] attribute is present), retrievable via Dto::primary(). When
 * multiple properties are tagged, the last one declared wins — there is only
 * one primary.
 */
class IsPrimary extends DtoAttribute
{
}
