<?php

declare(strict_types=1);

namespace orange\dto\sample;

use orange\dto\Dto;
use orange\dto\attributes\Column;
use orange\dto\attributes\FieldName;
use orange\dto\attributes\Label;
use orange\dto\attributes\Table;
use orange\dto\attributes\filters\ToFloat;
use orange\dto\attributes\filters\ToUpper;
use orange\dto\attributes\filters\Trim;
use orange\dto\attributes\validations\Between;
use orange\dto\attributes\validations\InList;
use orange\dto\attributes\validations\IsRequired;
use orange\dto\attributes\validations\ValidCreditCard;

/**
 * A payment submission, showing a Luhn-checked card number, a float-cast and
 * range-checked amount, and a normalized currency code.
 */
class Payment extends Dto
{
    #[Trim]
    #[IsRequired]
    #[ValidCreditCard]
    #[FieldName('card_number')]
    #[Column('card_number')]
    #[Table('payments')]
    #[Label('Card number')]
    public string $cardNumber;

    #[IsRequired]
    #[ToFloat]
    #[Between(0.5, 10000.0)]
    #[Column('amount')]
    #[Table('payments')]
    #[Label('Amount')]
    public float $amount;

    #[Trim]
    #[ToUpper]
    #[IsRequired]
    #[InList(['USD', 'EUR', 'GBP', 'CAD'])]
    #[Column('currency')]
    #[Table('payments')]
    #[Label('Currency')]
    public string $currency;
}
