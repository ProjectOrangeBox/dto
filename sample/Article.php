<?php

declare(strict_types=1);

namespace orange\dto\sample;

use orange\dto\Dto;
use orange\dto\attributes\Column;
use orange\dto\attributes\FieldName;
use orange\dto\attributes\Label;
use orange\dto\attributes\Table;
use orange\dto\attributes\filters\CollapseSpaces;
use orange\dto\attributes\filters\DefaultTo;
use orange\dto\attributes\filters\StripTags;
use orange\dto\attributes\filters\ToLower;
use orange\dto\attributes\filters\Trim;
use orange\dto\attributes\validations\DateFormat;
use orange\dto\attributes\validations\InList;
use orange\dto\attributes\validations\IsRequired;
use orange\dto\attributes\validations\MaxLength;
use orange\dto\attributes\validations\MinLength;
use orange\dto\attributes\validations\Slug;

/**
 * A CMS article submission, showing text clean-up filters alongside format
 * validators for slugs, publish dates, and a constrained status.
 */
class Article extends Dto
{
    #[Trim]
    #[CollapseSpaces]
    #[IsRequired]
    #[MaxLength(120)]
    #[Column('title')]
    #[Table('articles')]
    #[Label('Title')]
    public string $title;

    #[Trim]
    #[ToLower]
    #[IsRequired]
    #[Slug]
    #[MaxLength(120)]
    #[Column('slug')]
    #[Table('articles')]
    #[Label('Slug')]
    public string $slug;

    #[Trim]
    #[StripTags]
    #[IsRequired]
    #[MinLength(1)]
    #[Column('body')]
    #[Table('articles')]
    #[Label('Body')]
    public string $body;

    // DefaultTo supplies a value when the field is missing or empty, so the
    // InList check below always sees a value it can accept.
    #[DefaultTo('draft')]
    #[InList(['draft', 'published', 'archived'])]
    #[Column('status')]
    #[Table('articles')]
    #[Label('Status')]
    public string $status;

    #[Trim]
    #[IsRequired]
    #[DateFormat('Y-m-d')]
    #[FieldName('published_on')]
    #[Column('published_on')]
    #[Table('articles')]
    #[Label('Publish date')]
    public string $publishedOn;
}
