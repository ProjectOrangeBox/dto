<?php

declare(strict_types=1);

namespace orange\dto\attributes;

use Attribute;
use orange\dto\DtoAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * Tags a property that holds part of the record's primary key.
 *
 * A pure marker — it performs no validation or filtering. The Dto records the
 * tagged property's column name (the same key asColumns() uses), retrievable
 * via Dto::primaries() and Dto::primaryValues().
 *
 * More than one property may be tagged: several in one table make a compound
 * key, and one per #[Table] gives a multi-table Dto a key for each. The
 * singular Dto::primary()/primaryValue() then throw rather than answering with
 * half a key — narrow them with a table name, or ask the plural form.
 */
class IsPrimary extends DtoAttribute
{
}
