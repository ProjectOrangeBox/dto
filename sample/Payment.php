<?php

declare(strict_types=1);

namespace orange\request\sample;

use orange\request\Request;
use orange\request\attributes\Column;
use orange\request\attributes\FieldName;
use orange\request\attributes\Label;
use orange\request\attributes\Table;
use orange\request\attributes\filters\ToFloat;
use orange\request\attributes\filters\ToUpper;
use orange\request\attributes\filters\Trim;
use orange\request\attributes\validations\Between;
use orange\request\attributes\validations\InList;
use orange\request\attributes\validations\IsRequired;
use orange\request\attributes\validations\ValidCreditCard;

/**
 * A payment submission, showing a Luhn-checked card number, a float-cast and
 * range-checked amount, and a normalized currency code.
 */
class Payment extends Request
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
