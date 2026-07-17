<?php

declare(strict_types=1);

namespace orange\request\sample;

use orange\request\Request;
use orange\request\attributes\Column;
use orange\request\attributes\FieldName;
use orange\request\attributes\Label;
use orange\request\attributes\Table;
use orange\request\attributes\filters\CollapseSpaces;
use orange\request\attributes\filters\DefaultTo;
use orange\request\attributes\filters\StripTags;
use orange\request\attributes\filters\ToLower;
use orange\request\attributes\filters\Trim;
use orange\request\attributes\validations\DateFormat;
use orange\request\attributes\validations\InList;
use orange\request\attributes\validations\IsRequired;
use orange\request\attributes\validations\MaxLength;
use orange\request\attributes\validations\MinLength;
use orange\request\attributes\validations\Slug;

/**
 * A CMS article submission, showing text clean-up filters alongside format
 * validators for slugs, publish dates, and a constrained status.
 */
class Article extends Request
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
