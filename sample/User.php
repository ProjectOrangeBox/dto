<?php

declare(strict_types=1);

namespace orange\request\sample;

use orange\request\attributes\Column;
use orange\request\attributes\FieldName;
use orange\request\attributes\filters\ToInteger;
use orange\request\attributes\filters\ToString;
use orange\request\attributes\Label;
use orange\request\attributes\Table;
use orange\request\attributes\validations\GreaterThan;
use orange\request\attributes\validations\InList;
use orange\request\attributes\validations\IsRequired;
use orange\request\attributes\validations\LessThan;
use orange\request\attributes\validations\MaxLength;
use orange\request\attributes\validations\MinLength;
use orange\request\Request;

class User extends Request
{
  #[IsRequired]
  #[MaxLength(64)]
  #[MinLength(1)]
  #[Column('name')]
  #[Table('user')]
  #[ToString]
  #[Label('Name')]
  #[FieldName('fullname')]
  public string $name;

  #[IsRequired]
  #[ToInteger]
  #[GreaterThan(18)]
  #[LessThan(110)]
  #[Table('user')]
  #[Label('Age')]
  public int $age;

  #[IsRequired]
  #[MaxLength(16)]
  #[MinLength(4)]
  #[Column('fav_color')]
  #[Table('user')]
  #[ToString]
  #[FieldName('clr')]
  #[Label('Favorite Color')]
  public string $color;

  #[IsRequired]
  #[InList(['red', 'green', 'blue'])]
  public string $rgb;

  #[ToString]
  public string $notRequired;
}
