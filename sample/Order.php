<?php

declare(strict_types=1);

namespace orange\dto\sample;

use orange\dto\Dto;
use orange\dto\attributes\Column;
use orange\dto\attributes\Label;
use orange\dto\attributes\Table;
use orange\dto\attributes\filters\ToString;
use orange\dto\attributes\filters\Trim;
use orange\dto\attributes\validations\IsArray;
use orange\dto\attributes\validations\IsRequired;
use orange\dto\attributes\validations\MaxCount;
use orange\dto\attributes\validations\MaxLength;
use orange\dto\attributes\validations\MinCount;

/**
 * An order whose line items are a nested dto-array.
 *
 * #[IsArray(OrderLine::class)] builds an OrderLine from every element of the
 * "lines" input value and validates each one like a stand-alone DTO. Child
 * failures surface as a single error on this parent ("Order lines has 1 or
 * more errors"); extract $order->lines and read each child's own errors()
 * for the detail. asArray()/json flatten the children into nested plain
 * arrays, while the db shapes skip the property entirely — persist each line
 * individually through its own asColumns()/asTable().
 */
class Order extends Dto
{
    #[Trim]
    #[IsRequired]
    #[MaxLength(64)]
    #[ToString]
    #[Column('customer')]
    #[Table('orders')]
    #[Label('Customer')]
    public protected(set) string $customer;

    #[IsRequired]
    #[IsArray(OrderLine::class)]
    #[MinCount(1)]
    #[MaxCount(5)]
    #[Label('Order lines')]
    public protected(set) array $lines;
}
