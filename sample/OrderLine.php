<?php

declare(strict_types=1);

namespace orange\dto\sample;

use orange\dto\Dto;
use orange\dto\attributes\Column;
use orange\dto\attributes\Label;
use orange\dto\attributes\Table;
use orange\dto\attributes\filters\ToInteger;
use orange\dto\attributes\filters\ToUpper;
use orange\dto\attributes\filters\Trim;
use orange\dto\attributes\validations\Between;
use orange\dto\attributes\validations\BetweenLength;
use orange\dto\attributes\validations\IsRequired;

/**
 * A single order line — the element type of Order's nested dto-array.
 *
 * A completely ordinary DTO: it has its own filters, validations, and db
 * shapes, and can be used stand-alone. Order builds one of these per element
 * of its "lines" input via #[IsArray(OrderLine::class)].
 */
class OrderLine extends Dto
{
    #[Trim]
    #[ToUpper]
    #[IsRequired]
    #[BetweenLength(2, 16)]
    #[Column('sku')]
    #[Table('order_lines')]
    #[Label('Sku')]
    public protected(set) string $sku;

    #[IsRequired]
    #[ToInteger]
    #[Between(1, 99)]
    #[Column('qty')]
    #[Table('order_lines')]
    #[Label('Quantity')]
    public protected(set) int $qty;
}
