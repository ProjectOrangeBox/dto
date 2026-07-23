# orange/dto

`orange/dto` is a small, dependency-free PHP 8.4+ package for building
validated, filtered data transfer objects (DTOs) from raw input arrays using
PHP attributes.

You declare a DTO class that extends `orange\dto\Dto`, annotate each
publicly readable property with attributes, and the package will:

- read each value from the incoming input array (by field name)
- validate every value against the rules you declared
- filter / cast values (trim, lower-case, cast to int, etc.)
- expose the valid data as typed properties and as array / column / table shapes
- collect human-readable error messages for anything that failed

> **Status:** work in progress. The public API described here is stable and
> covered by tests, but new attributes are still being added.

## Requirements

- PHP `>= 8.4`
- Extensions: `ext-filter`, `ext-json`, `ext-mbstring` (declared in `composer.json`)

## Installation

```sh
composer require orange/dto
```

## Quick Start

```php
<?php

declare(strict_types=1);

namespace app\request;

use orange\dto\Dto;
use orange\dto\attributes\Column;
use orange\dto\attributes\FieldName;
use orange\dto\attributes\Label;
use orange\dto\attributes\Table;
use orange\dto\attributes\filters\ToInteger;
use orange\dto\attributes\filters\ToString;
use orange\dto\attributes\filters\Trim;
use orange\dto\attributes\validations\Between;
use orange\dto\attributes\validations\IsRequired;
use orange\dto\attributes\validations\MaxLength;
use orange\dto\attributes\validations\MinLength;

class UserRequest extends Dto
{
    #[Trim]
    #[ToString]
    #[IsRequired]
    #[MinLength(1)]
    #[MaxLength(64)]
    #[Column('name')]
    #[Table('user')]
    #[Label('Name')]
    public protected(set) string $name;

    #[ToInteger]
    #[IsRequired]
    #[Between(18, 110)]
    #[Column('age')]
    #[Table('user')]
    #[Label('Age')]
    public protected(set) int $age;

    #[Trim]
    #[ToString]
    #[IsRequired]
    #[MinLength(4)]
    #[MaxLength(16)]
    #[FieldName('clr')]     // read from input key "clr"
    #[Column('fav_color')]  // store as column "fav_color"
    #[Table('user')]
    #[Label('Favorite Color')]
    public protected(set) string $color;
}

$request = new UserRequest([
    'name' => 'Johnny Appleseed',
    'age'  => '23',
    'clr'  => 'Orange',
]);

if ($request->isValid()) {
    echo $request->name;            // "Johnny Appleseed"
    echo $request->age;             // 23 (int)
    print_r($request->asColumns()); // ['name' => ..., 'age' => 23, 'fav_color' => 'Orange']
} else {
    print_r($request->errors());
}
```

## How It Works

When you construct a request, it uses reflection to find every publicly
*readable* property that carries one or more `orange\dto` attributes — plain
`public` and asymmetric `public protected(set)` both qualify. For each such
property it:

1. resolves the **field name** (input key), **column**, **table**, and **label**
   from the metadata attributes (falling back to the property name);
2. reads the raw value from the input array using the field name;
3. walks the attributes **in declaration order**, calling each rule's `validate()`
   and/or `filter()` method against the current value;
4. if every validation passed, assigns the (possibly filtered) value to the typed
   property and records it in the array / column / table outputs. If anything
   failed, the messages are collected under the field name instead.

Properties with no `orange\dto` attributes are ignored entirely.

### Attribute order matters

Validations and filters run in a **single pass in declaration order**, and a
filter changes the value seen by later attributes. Declare value-shaping filters
(`Trim`, `ToInteger`, `ToLower`, …) **before** the validations that depend on
them:

```php
#[Trim]          // "  9 " -> "9"
#[ToInteger]     // "9"    -> 9
#[Between(1, 10)] // validates 9
public int $qty;
```

## Reading Results

| Method | Returns |
| --- | --- |
| `isValid(): bool` | `true` when there are no errors |
| `errors(): array` | `['fieldName' => ['message', ...], ...]` |
| `allErrors(): array` | `errors()` plus [nested DTO](#nested-dtos) detail, dot-keyed: `['lines.1.sku' => [...], ...]` |
| `asArray(): array` | valid values keyed by **property name** |
| `asColumns(bool $withoutPrimary = false): array` | valid values keyed by **column name**; `true` drops the `#[IsPrimary]` column |
| `asTable(false\|string $table = false, bool $withoutPrimary = false): array` | valid values grouped by **table** (all tables, or one named table); `true` drops the `#[IsPrimary]` column from its table |
| `only(string ...$props): array` | `asArray()` restricted to the given property names |
| `except(string ...$props): array` | `asArray()` without the given property names |
| `input(?string $key = null, mixed $default = ''): mixed` | the raw, unprocessed input (whole array, or one key) |

`asTable('name')` throws `\OutOfBoundsException` if the requested table does not exist.

`$withoutPrimary` produces the shape for insert/update SET clauses — the
primary is auto-assigned on insert and targeted through the WHERE on update,
so it is never a SET column:

```php
$sql->insert()->set($dto->asColumns(withoutPrimary: true));
$sql->update()->set($dto->asColumns(withoutPrimary: true))->wherePrimary($dto->primaryValue());
```

`except()` is the answer to the "every valid field is mapped into every output"
gotcha below — drop fields that validate but never persist:

```php
$registration->except('passwordConfirmation'); // everything else, keyed by property
```

### JSON

`Dto` implements `JsonSerializable`: `json_encode($dto)` — or a list of DTOs —
emits exactly `asArray()`. Invalid fields are omitted and engine internals can
never leak into the encoding, so a DTO (or an array of them) can be passed
straight to a JSON response.

### Debugging

`__debugInfo()` curates `var_dump($dto)` down to what matters — the validity
flag, the validated values, and the errors — instead of the raw input and
internal bookkeeping structures.

### Inspecting which fields passed / failed

| Method | Returns |
| --- | --- |
| `validKeys(bool $raw = true): array` | keys of fields that passed |
| `invalidKeys(bool $raw = true): array` | keys of fields that failed |
| `validInputKeys(): array` | passed fields, as resolved input field names |
| `invalidInputKeys(): array` | failed fields, as resolved input field names |
| `primary(): ?string` | column name of the `#[IsPrimary]` property — its `#[Column]` name, else its resolved field name; `null` when none is tagged |
| `primaryValue(): mixed` | the `#[IsPrimary]` property's **validated value** — `null` when none is tagged or it failed validation |

By default (`$raw = true`) these return the **raw property names**. Pass `false`
— or use the `*InputKeys()` wrappers — to get the resolved input field names (the
remapped `FieldName` values). For the `color`/`clr` property above:

```php
$request->validKeys();       // ['name', 'age', 'color']  (property names)
$request->validInputKeys();  // ['name', 'age', 'clr']    (input field names)
```

### Resolving a property's metadata

```php
$request->fieldName('color'); // 'clr'
$request->column('color');    // 'fav_color'
$request->table('color');     // 'user'
$request->label('color');     // 'Favorite Color'
```

Each falls back to the property name when the corresponding attribute is absent.

## Output Shapes

Given the `UserRequest` above with valid input:

```php
$request->asArray();
// ['name' => 'Johnny Appleseed', 'age' => 23, 'color' => 'Orange']

$request->asColumns();
// ['name' => 'Johnny Appleseed', 'age' => 23, 'fav_color' => 'Orange']

$request->asTable();
// ['user' => ['name' => 'Johnny Appleseed', 'age' => 23, 'fav_color' => 'Orange']]

$request->asTable('user');
// ['name' => 'Johnny Appleseed', 'age' => 23, 'fav_color' => 'Orange']
```

## Metadata Attributes

| Attribute | Purpose |
| --- | --- |
| `#[FieldName('key')]` | input array key to read from (defaults to property name) |
| `#[Column('col')]` | column name used by `asColumns()` / `asTable()` (defaults to property name) |
| `#[Table('name', 'database')]` | table to group under in `asTable()`; optional database identifier |
| `#[Label('Human Name')]` | name used in error messages (defaults to property name) |
| `#[IsPrimary]` | tags the property holding the record's primary key — a pure marker. Its column name is retrievable via `primary()`. When multiple properties are tagged the last declared wins — there is only one primary |
| `#[DbCast('int')]` | scalar cast (`int`, `float`, `string`, `bool`) applied to the value in `asColumns()` / `asTable()` **only** — the typed property, `asArray()`, and JSON keep the domain value. `null` is never cast. An unknown target throws `InvalidArgumentException` at the class's first construction |

### Domain vs. storage types — `DbCast`

Filters shape the **domain** value on the way in; `DbCast` shapes the
**storage** value on the way out. The motivating case is a bool property whose
column is an integer — without the cast, binding PHP `false` sends `''`, which
strict-mode MySQL rejects for an `int` column:

```php
#[IsBoolean]
#[ToBoolean]
#[DbCast('int')]
public bool $in_office;
```

```php
$dto->in_office;      // true          (domain: bool, also in asArray()/JSON)
$dto->asColumns();    // ['in_office' => 1]  (storage: int, ready to bind)
```

## Filter Attributes

Filters transform the value and never fail.

| Attribute | Effect |
| --- | --- |
| `#[Trim]` | strips surrounding whitespace |
| `#[CollapseSpaces]` | collapses internal whitespace runs to single spaces and trims |
| `#[StripTags]` | removes HTML/PHP tags |
| `#[ToLower]` / `#[ToUpper]` | multibyte case conversion |
| `#[StrLimit(int $length)]` | truncates a string to `$length` characters |
| `#[ToBoolean]` | `true`/`"true"`/`"yes"`/`"on"`/`"1"`/non-zero int → `true`; everything else → `false` |
| `#[ToInteger]` | casts to `int` |
| `#[ToFloat]` | casts to `float` |
| `#[ToString]` | casts to `string` |
| `#[NullIfEmpty]` | converts an empty string `''` to `null` (leaves `'0'` / `0` alone) |
| `#[DefaultTo(mixed $default = null)]` | substitutes `$default` when the value is `null` or `''` |
| `#[Slugify]` | lower-cases and converts to a hyphen-separated slug (`"My Post!"` → `"my-post"`) |
| `#[HtmlEncode]` | encodes HTML special characters (`htmlspecialchars`, `ENT_QUOTES`) |
| `#[Round(int $precision = 0)]` | rounds numeric input to `$precision` decimal places |
| `#[OnlyDigits]` | strips every non-digit character |
| `#[UcWords]` | title-cases each word (multibyte-safe) |
| `#[UcFirst]` | upper-cases just the first character (multibyte-safe) |
| `#[HtmlDecode]` | decodes HTML entities — the inverse of `#[HtmlEncode]` |
| `#[OnlyAlpha]` | strips every non-letter character |
| `#[OnlyAlphaNumeric]` | strips every character that is not a letter or digit |
| `#[Clamp(int\|float $min, int\|float $max)]` | forces numeric input into `[min, max]` — the filter counterpart of `#[Between]` |
| `#[Ceil]` / `#[Floor]` | rounds numeric input up / down to a whole number |
| `#[Abs]` | absolute value of numeric input |
| `#[StripControlChars]` | removes control and zero-width characters (keeps tabs and newlines) |
| `#[NormalizeLineEndings]` | converts `\r\n` and `\r` line endings to `\n` |
| `#[StripSpaces]` | removes all whitespace (card numbers, codes) |
| `#[Transliterate]` | folds accents to ASCII (`é` → `e`); uses intl when available, iconv otherwise |
| `#[Pad(int $length, string $padString = '0')]` | left-pads strings/integers to a fixed length (`42` → `00042`) |
| `#[NormalizeDateTime(string $format = 'Y-m-d H:i:s')]` | reformats any `strtotime()`-parseable date to a canonical format; unparseable input passes through |
| `#[NormalizePhone]` | strips phone formatting, keeping digits and a leading `+` |

## Validation Attributes

Every validation attribute also accepts an optional custom message as its **last**
constructor argument (see [Custom error messages](#custom-error-messages)).

### Character / string content

| Attribute | Passes when the value… |
| --- | --- |
| `#[Alpha]` | contains only letters |
| `#[AlphaDash]` | contains only letters and dashes |
| `#[AlphaNumeric]` | contains only letters and digits |
| `#[AlphaNumericSpaces]` | contains only letters, digits, and spaces |
| `#[Slug]` | is a lower-case, hyphen-separated slug (`my-post-1`) |
| `#[StartsWith(string $needle)]` | starts with `$needle` |
| `#[EndsWith(string $needle)]` | ends with `$needle` |
| `#[Contains(string $needle)]` | contains `$needle` |
| `#[NotContains(string $needle)]` | does not contain `$needle` |
| `#[RegexMatch(string $pattern)]` | matches the PCRE `$pattern` |
| `#[NotRegexMatch(string $pattern)]` | does not match the PCRE `$pattern` |

### Numbers

| Attribute | Passes when the value… |
| --- | --- |
| `#[Numeric]` | is numeric |
| `#[Integer]` | is an integer |
| `#[Decimal]` | is a decimal number (has a fractional part) |
| `#[IsNatural]` | is a natural number (`0` and up) |
| `#[IsNaturalNoZero]` | is a natural number greater than zero |
| `#[GreaterThan(int\|float $value)]` | is greater than `$value` |
| `#[GreaterThanEqualTo(int\|float $value)]` | is greater than or equal to `$value` |
| `#[LessThan(int\|float $value)]` | is less than `$value` |
| `#[LessThanEqualTo(int\|float $value)]` | is less than or equal to `$value` |
| `#[Between(int\|float $min, int\|float $max)]` | is within `[min, max]` (inclusive) |
| `#[MultipleOf(int\|float $step)]` | is an exact multiple of `$step` |

### Length

| Attribute | Passes when the string length… |
| --- | --- |
| `#[ExactLength(int $length)]` | equals `$length` |
| `#[MaxLength(int $length)]` | is **strictly less than** `$length` |
| `#[MinLength(int $length)]` | is **strictly greater than** `$length` |
| `#[BetweenLength(int $min, int $max)]` | is within `[min, max]` (inclusive) |

> Note: `MaxLength` and `MinLength` are strict (`<` and `>`), matching their
> messages ("must be less/greater than N characters"). Use `BetweenLength` or
> `ExactLength` when you need inclusive bounds.

### Presence & comparison

| Attribute | Passes when… |
| --- | --- |
| `#[IsRequired]` | the value is "filled" — not `null`, `''`, or `[]` (`'0'` and `0` **do** count as filled) |
| `#[Matches(string $field)]` | the value equals another field's input value |
| `#[Differs(string $field)]` | the value differs from another field's input value |
| `#[RequiredIf(string $field, string $value)]` | present, but only required when `$field` equals `$value` |
| `#[RequiredWith(string $field)]` | present, but only required when `$field` is filled |
| `#[RequiredUnless(string $field, string $value)]` | required except when `$field` equals `$value` |
| `#[RequiredWithout(string $field)]` | required when `$field` is empty |
| `#[ProhibitedIf(string $field, string $value)]` | must be empty when `$field` equals `$value` |
| `#[ProhibitedWith(string $field)]` | must be empty when `$field` is filled — the two fields are mutually exclusive |
| `#[InList(array $values)]` | is one of `$values` |
| `#[NotInList(array $values)]` | is none of `$values` |
| `#[Equals(mixed $value)]` | equals the fixed literal `$value` (compared as strings) |
| `#[NotEquals(mixed $value)]` | differs from the fixed literal `$value` (compared as strings) |
| `#[Accepted]` | is a truthy "checkbox" value: `true`, `1`, `'1'`, `'yes'`, `'on'`, or `'true'` |

### Formats

| Attribute | Passes when the value is… |
| --- | --- |
| `#[ValidEmail]` | a valid email address |
| `#[ValidEmails]` | a comma-separated list of valid email addresses |
| `#[ValidUrl]` | a valid URL |
| `#[ValidHostname]` | a valid hostname |
| `#[ValidIp(string $version = '')]` | a valid IP (`''` = any, `'ipv4'`, or `'ipv6'`) |
| `#[ValidDate]` | a date string parseable by `strtotime()` |
| `#[DateFormat(string $format = 'Y-m-d')]` | an exact match for the given date `$format` |
| `#[Before(string $date)]` | a date strictly before `$date` (anything `strtotime()` understands, including `'now'`) |
| `#[After(string $date)]` | a date strictly after `$date` (anything `strtotime()` understands, including `'now'`) |
| `#[MinAge(int $years)]` | a date at least `$years` years in the past (date-of-birth rules) |
| `#[MaxAge(int $years)]` | a date no more than `$years` years in the past |
| `#[ValidUlid]` | a ULID (26 characters of Crockford base32) |
| `#[ValidIban]` | an IBAN, verified with the ISO 7064 mod-97 checksum (spaces and case ignored) |
| `#[ValidIsbn]` | an ISBN-10 or ISBN-13 including its checksum (hyphens and spaces ignored) |
| `#[ValidLuhn]` | passes the Luhn mod-10 checksum (IMEIs, account numbers; use `#[ValidCreditCard]` for cards) |
| `#[ValidMacAddress]` | a MAC address (colon, hyphen, or dot notation) |
| `#[ValidPort]` | a network port number (1–65535) |
| `#[ValidSemver]` | a semantic version per semver.org 2.0.0 (`1.2.3`, `2.0.0-rc.1`) |
| `#[ValidFilename]` | a safe bare filename — no separators, traversal, control characters, or null bytes |

### Arrays

Multi-select and checkbox-group inputs arrive as arrays; these rules validate
the array itself:

| Attribute | Passes when the value… |
| --- | --- |
| `#[IsArray(?string $dtoClass = null)]` | is an array; given a Dto class, each element is also built and validated as a child DTO (see [Nested DTOs](#nested-dtos)) |
| `#[MinCount(int $count)]` | is an array with at least `$count` elements |
| `#[MaxCount(int $count)]` | is an array with at most `$count` elements |
| `#[InListEach(array $values)]` | is an array whose every element is one of `$values` |
| `#[BeforeField(string $field)]` | a date strictly before another field's date value |
| `#[AfterField(string $field)]` | a date strictly after another field's date value |
| `#[ValidTimezone]` | a valid PHP timezone identifier |
| `#[ValidJson]` | a well-formed JSON string |
| `#[ValidUuid]` | a valid RFC 4122 UUID (versions 1–8) |
| `#[ValidBase64]` | a valid base64 string |
| `#[ValidHexColor]` | a 3- or 6-digit hex color, with optional leading `#` |
| `#[ValidCreditCard]` | a 13–19 digit number passing the Luhn checksum |
| `#[ValidPhoneNumber]` | a plausible phone number — a loose check, not strict E.164 (formatting characters are stripped, then 7–15 digits with an optional leading `+` are required) |
| `#[ValidCountryCode]` | a valid ISO 3166-1 alpha-2 country code (case-insensitive) |
| `#[ValidCurrencyCode]` | a valid ISO 4217 alpha-3 currency code (case-insensitive) |

## Nested DTOs

`#[IsArray(Child::class)]` turns a property into an array of child DTOs. Each
element of the input value must itself be an array; each one is passed to
`new Child($element)` — built, filtered, and validated exactly like a
stand-alone DTO — with the input keys preserved:

```php
class LineItem extends Dto
{
    #[IsRequired]
    #[ToString]
    #[Column('sku')]
    #[Table('order_lines')]
    #[Label('Sku')]
    public protected(set) string $sku;

    #[IsRequired]
    #[ToInteger]
    #[GreaterThan(0)]
    #[Column('qty')]
    #[Table('order_lines')]
    #[Label('Qty')]
    public protected(set) int $qty;
}

class OrderRequest extends Dto
{
    #[IsRequired]
    #[IsArray(LineItem::class)]
    #[MinCount(1)]
    #[MaxCount(10)]
    #[Label('Lines')]
    public protected(set) array $lines;
}

$request = new OrderRequest([
    'lines' => [
        ['sku' => 'A1', 'qty' => '2'],
        ['sku' => 'B2', 'qty' => 1],
    ],
]);

$request->lines[0];        // a LineItem instance
$request->lines[0]->qty;   // 2 (filtered by the child's own ToInteger)
```

`#[MinCount]` / `#[MaxCount]` bound the element count as usual, and the class
argument is verified up front — a non-Dto class throws at the owning class's
first construction.

### Child errors roll up as one parent error

A child failure never floods the parent's `errors()`. The parent reports a
single message per problem, most fundamental first:

| Situation | Parent error |
| --- | --- |
| the value is not an array | `Lines must be an array` |
| an element is not itself an array | `Lines contains a non-object entry` |
| one or more children failed validation | `Lines has 1 or more errors` |

For the detail, extract the property — it is assigned the child DTOs even
when they failed, so each child can be inspected like any other DTO:

```php
if (!$request->isValid()) {
    foreach ($request->lines as $index => $line) {
        if (!$line->isValid()) {
            $lineErrors = $line->errors(); // a normal DTO errors() array
        }
    }
}
```

When the consumer doesn't know the shape — a generic API error body, a log
entry — `allErrors()` exports the full tree in one call, dot-keyed by input
field name and element key, recursing through any deeper dto-arrays:

```php
$request->allErrors();
// [
//     'lines'       => ['Lines has 1 or more errors'],
//     'lines.1.sku' => ['Sku is required'],
//     'lines.1.qty' => ['Quantity must be between 1 and 99'],
// ]
```

The output shapes are unaffected by this extraction escape hatch: an invalid
dto-array field is omitted from `asArray()` / JSON like any other invalid
field.

### Output

`asArray()` (and therefore `json_encode()`, `only()`, `except()`) flattens
each child through its own `asArray()`, so the result is nested plain arrays
all the way down — no objects to unwrap:

```php
$request->asArray();
// ['lines' => [['sku' => 'A1', 'qty' => 2], ['sku' => 'B2', 'qty' => 1]]]
```

The db shapes are the exception: a nested structure has no single-row
table/column representation, so `asColumns()` / `asTable()` on the parent
skip dto-array properties entirely. Persist the children individually:

```php
foreach ($request->lines as $line) {
    $db->insert('order_lines', $line->asColumns());
}
```

An absent optional dto-array normalizes to `[]`, so the typed `array`
property is always safe to iterate.

## Custom Error Messages

Every validation attribute accepts a custom message string. It is passed through `sprintf()`
with the label as the first argument, followed by any rule-specific values, so you
can use `%s` placeholders (or none at all):

```php
#[IsRequired('You must provide a name.')]
public string $name;

#[MinLength(8, '%s must be at least %s characters long.')]
public string $password;   // -> "Password must be at least 8 characters long."
```

The rule-specific values match what appears in the default message (for
`MinLength` that is the length; for `Between` the min then max, etc.). Use `%%` to
output a literal percent sign.

## Notes & Gotchas

- **Declare properties `public protected(set)` to make the DTO immutable.**
  The engine assigns validated values from inside the class hierarchy, so
  asymmetric visibility costs nothing — but it stops outside code from
  overwriting a property *after* validation, meaning an instance can only ever
  hold what its rules let through. Plain `public` still works if you want
  writable properties.
- **Only valid fields appear in output.** A field that fails validation is not
  assigned to its typed property (reading it throws an "uninitialized" error) and
  is excluded from `asArray()` / `asColumns()` / `asTable()`.
- **Typed properties must match filtered values.** If a property is typed `int`,
  make sure a cast filter such as `#[ToInteger]` runs, otherwise assigning a
  string will raise a `TypeError`.
- **Every valid field is mapped into every output.** A field with no `#[Table]` /
  `#[Column]` still appears in `asTable()` / `asColumns()` under its property name.
  Filter such fields (e.g. a password confirmation) out downstream.
- **Format validators always run.** There is no "sometimes" concept — a format
  validator like `#[ValidEmail]` will fail on an empty value even alongside
  `#[RequiredIf]`. Pair conditional rules with presence-only checks.
- **"Filled" isn't PHP's `empty()`.** `#[IsRequired]`, `#[RequiredIf]`, and
  `#[RequiredWith]` treat a value as present unless it's `null`, `''`, or `[]` —
  the string `'0'` and the integer `0` both count as filled, unlike `empty()`.

## Using It With the Orange Framework

`orange/dto` has no dependency on `orange/framework` — a `Dto` subclass
just needs a plain array. The only framework touchpoint is *where that array
comes from*, which is the framework's `Input` service
(`orange\framework\interfaces\InputInterface`, wired up as `$this->input` by
`orange\framework\controllers\BaseController` via `#[AttachService('input')]`).

`Input` exposes the request body and the query string separately — there's no
combined "all input" method:

- `$this->input->request()` — the POST/PUT/PATCH body (also parses JSON bodies)
- `$this->input->query()` — the query string (`$_GET`)

If an endpoint needs both, merge them yourself, e.g.
`array_merge($this->input->query(), $this->input->request())` (later keys win).

### 1. Build it inline (simplest)

```php
namespace app\users\controllers;

use orange\framework\controllers\BaseController;
use app\users\requests\CreateUserRequest;
use app\users\requests\SearchUsersRequest;

class UserController extends BaseController
{
    public function store(): string
    {
        $request = new CreateUserRequest($this->input->request());

        if (!$request->isValid()) {
            // handle $request->errors() however this controller reports failures
        }

        // ... persist $request->asColumns() / asTable('user') ...

        return '';
    }

    public function search(): string
    {
        $request = new SearchUsersRequest($this->input->query());

        // ...

        return '';
    }
}
```

### 2. Register it as a container service

To avoid instantiating it by hand in every method, register a factory in
`config/services.php` — the same pattern this app already uses for its `files`
service — and pull it in with `#[AttachService]`:

```php
// config/services.php
use orange\framework\interfaces\ContainerInterface;
use app\users\requests\CreateUserRequest;

return [
    'createUserRequest' => function (ContainerInterface $container) {
        return new CreateUserRequest($container->input->request());
    },
];
```

```php
use orange\framework\attributes\AttachService;
use orange\framework\controllers\BaseController;
use app\users\requests\CreateUserRequest;

class UserController extends BaseController
{
    #[AttachService('createUserRequest')]
    protected CreateUserRequest $createUserRequest;

    public function store(): string
    {
        if (!$this->createUserRequest->isValid()) {
            // ...
        }

        return '';
    }
}
```

The container resolves each service once per request, so the `Dto` is
built exactly once, from that request's own input — the same lifecycle as the
`config`, `input`, and `output` services `BaseController` already attaches.

### 3. JSON APIs: reporting validation failures

`orange\framework\controllers\JsonController` already maps a `validationFail`
status to HTTP 406 in its `$restSuccessMap`, so a failed request reports
through the same `response()` helper used everywhere else:

```php
namespace app\users\controllers;

use orange\framework\controllers\JsonController;
use app\users\requests\CreateUserRequest;

class UserApiController extends JsonController
{
    public function store(): string
    {
        $request = new CreateUserRequest($this->input->request());

        if (!$request->isValid()) {
            // allErrors() over errors() so nested dto-array detail reaches
            // the client instead of a single "has 1 or more errors" rollup
            $this->data['errors'] = $request->allErrors();

            return $this->response('validationFail'); // 406
        }

        // ... persist $request->asColumns() / asTable('user') ...

        $this->data = $request->asArray();

        return $this->response('create'); // 201
    }
}
```

Dto subclasses have no required location — put them wherever your module
organizes its code, e.g. `application/<module>/requests/` or
`api/<module>/requests/`, following the same HMVC layout as the rest of the
module.

## Samples

The `sample/` directory contains runnable request classes covering the full
attribute set — sign-up, CMS article, payment, API settings, conditional
contact preferences, and an order with nested dto-array line items
(`Order` / `OrderLine`, including extracting child errors after a failure).
Run them all against valid and invalid input:

```sh
php sample/run.php
```

## Testing

The package ships a PHPUnit suite under `unittests/` with a `phpunit.xml.dist`:

```sh
composer test            # run the suite
composer test-coverage   # run with a text coverage report (needs Xdebug or PCOV)
```

## License

MIT. See [LICENSE](LICENSE).
