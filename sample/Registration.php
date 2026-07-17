<?php

declare(strict_types=1);

namespace orange\request\sample;

use orange\request\Request;
use orange\request\attributes\Column;
use orange\request\attributes\FieldName;
use orange\request\attributes\Label;
use orange\request\attributes\Table;
use orange\request\attributes\filters\ToInteger;
use orange\request\attributes\filters\ToLower;
use orange\request\attributes\filters\Trim;
use orange\request\attributes\validations\AlphaNumeric;
use orange\request\attributes\validations\Between;
use orange\request\attributes\validations\BetweenLength;
use orange\request\attributes\validations\IsRequired;
use orange\request\attributes\validations\Matches;
use orange\request\attributes\validations\MaxLength;
use orange\request\attributes\validations\ValidEmail;

/**
 * A user sign-up form.
 *
 * Note the attribute ORDER: value-shaping filters such as Trim and ToLower are
 * declared BEFORE the validations that depend on them, because the request
 * engine applies filters and validations in a single pass in declaration order.
 */
class Registration extends Request
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
