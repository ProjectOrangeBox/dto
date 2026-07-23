<?php

declare(strict_types=1);

namespace orange\dto\sample;

use orange\dto\attributes\Column;
use orange\dto\attributes\FieldName;
use orange\dto\attributes\filters\ToInteger;
use orange\dto\attributes\filters\ToString;
use orange\dto\attributes\Label;
use orange\dto\attributes\Table;
use orange\dto\attributes\validations\GreaterThan;
use orange\dto\attributes\validations\InList;
use orange\dto\attributes\validations\IsRequired;
use orange\dto\attributes\validations\LessThan;
use orange\dto\attributes\validations\MaxLength;
use orange\dto\attributes\validations\MinLength;
use orange\dto\Dto;

class User extends Dto
{
    #[IsRequired]
    #[MaxLength(64)]
    #[MinLength(1)]
    #[Column('name')]
    #[Table('user')]
    #[ToString]
    #[Label('Name')]
    #[FieldName('fullname')]
    public protected(set) string $name;

    #[IsRequired]
    #[ToInteger]
    #[GreaterThan(18)]
    #[LessThan(110)]
    #[Table('user')]
    #[Label('Age')]
    public protected(set) int $age;

    #[IsRequired]
    #[MaxLength(16)]
    #[MinLength(4)]
    #[Column('fav_color')]
    #[Table('user')]
    #[ToString]
    #[FieldName('clr')]
    #[Label('Favorite Color')]
    public protected(set) string $color;

    #[IsRequired]
    #[InList(['red', 'green', 'blue'])]
    public protected(set) string $rgb;

    #[ToString]
    public protected(set) string $notRequired;
}
