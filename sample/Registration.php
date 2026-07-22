<?php

declare(strict_types=1);

namespace orange\dto\sample;

use orange\dto\Dto;
use orange\dto\attributes\Column;
use orange\dto\attributes\FieldName;
use orange\dto\attributes\Label;
use orange\dto\attributes\Table;
use orange\dto\attributes\filters\ToInteger;
use orange\dto\attributes\filters\ToLower;
use orange\dto\attributes\filters\Trim;
use orange\dto\attributes\validations\AlphaNumeric;
use orange\dto\attributes\validations\Between;
use orange\dto\attributes\validations\BetweenLength;
use orange\dto\attributes\validations\IsRequired;
use orange\dto\attributes\validations\Matches;
use orange\dto\attributes\validations\MaxLength;
use orange\dto\attributes\validations\ValidEmail;

/**
 * A user sign-up form.
 *
 * Note the attribute ORDER: value-shaping filters such as Trim and ToLower are
 * declared BEFORE the validations that depend on them, because the request
 * engine applies filters and validations in a single pass in declaration order.
 */
class Registration extends Dto
{
    #[Trim]
    #[ToLower]
    #[IsRequired]
    #[BetweenLength(3, 20)]
    #[AlphaNumeric]
    #[Column('username')]
    #[Table('users')]
    #[Label('Username')]
    public string $username;

    #[Trim]
    #[ToLower]
    #[IsRequired]
    #[ValidEmail]
    #[MaxLength(255)]
    #[Column('email')]
    #[Table('users')]
    #[Label('Email')]
    public string $email;

    #[IsRequired]
    #[BetweenLength(8, 72)]
    #[Column('password')]
    #[Table('users')]
    #[Label('Password')]
    public string $password;

    // Every valid field is included in asArray()/asColumns()/asTable(). This
    // confirmation field has no #[Table]/#[Column], so it defaults to its own
    // property name in those outputs — filter it out downstream before saving.
    #[IsRequired]
    #[Matches('password')]
    #[FieldName('password_confirmation')]
    #[Label('Password confirmation')]
    public string $passwordConfirmation;

    #[IsRequired]
    #[ToInteger]
    #[Between(13, 120)]
    #[Column('age')]
    #[Table('users')]
    #[Label('Age')]
    public int $age;
}
