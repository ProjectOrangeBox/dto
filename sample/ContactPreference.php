<?php

declare(strict_types=1);

namespace orange\dto\sample;

use orange\dto\Dto;
use orange\dto\attributes\Column;
use orange\dto\attributes\FieldName;
use orange\dto\attributes\Label;
use orange\dto\attributes\Table;
use orange\dto\attributes\filters\NullIfEmpty;
use orange\dto\attributes\filters\Trim;
use orange\dto\attributes\validations\InList;
use orange\dto\attributes\validations\IsRequired;
use orange\dto\attributes\validations\NotInList;
use orange\dto\attributes\validations\RequiredIf;
use orange\dto\attributes\validations\RequiredWith;

/**
 * A contact-preferences form demonstrating conditional requiredness.
 *
 * RequiredIf / RequiredWith only enforce PRESENCE, and only when their trigger
 * condition is met — so a field that is not currently required passes even when
 * empty. (Format validators like ValidEmail always run, so pair them with these
 * rules only when the field is expected to be filled.)
 */
class ContactPreference extends Dto
{
    #[Trim]
    #[IsRequired]
    #[InList(['email', 'phone', 'none'])]
    #[FieldName('contact_method')]
    #[Column('contact_method')]
    #[Table('contacts')]
    #[Label('Contact method')]
    public string $contactMethod;

    // Required only when contact_method is "email".
    #[Trim]
    #[RequiredIf('contact_method', 'email')]
    #[FieldName('email')]
    #[Column('email')]
    #[Table('contacts')]
    #[Label('Email')]
    public string $email;

    // Required only when contact_method is "phone".
    #[Trim]
    #[RequiredIf('contact_method', 'phone')]
    #[FieldName('phone')]
    #[Column('phone')]
    #[Table('contacts')]
    #[Label('Phone')]
    public string $phone;

    #[Trim]
    #[IsRequired]
    #[NotInList(['admin', 'root', 'system'])]
    #[Column('handle')]
    #[Table('contacts')]
    #[Label('Handle')]
    public string $handle;

    // An optional promo code: NullIfEmpty keeps it valid whether present or not.
    #[Trim]
    #[NullIfEmpty]
    #[FieldName('promo_code')]
    #[Column('promo_code')]
    #[Table('contacts')]
    #[Label('Promo code')]
    public ?string $promoCode;

    // Required only when a promo code was supplied.
    #[Trim]
    #[RequiredWith('promo_code')]
    #[FieldName('referral')]
    #[Column('referral')]
    #[Table('contacts')]
    #[Label('Referral')]
    public string $referral;
}
