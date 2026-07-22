<?php

declare(strict_types=1);

use orange\dto\Dto;
use orange\dto\attributes\Column;
use orange\dto\attributes\FieldName;
use orange\dto\attributes\Label;
use orange\dto\attributes\Table;
use orange\dto\attributes\filters\ToInteger;
use orange\dto\attributes\filters\ToString;
use orange\dto\attributes\validations\GreaterThan;
use orange\dto\attributes\validations\IsRequired;
use orange\dto\attributes\validations\LessThan;
use orange\dto\attributes\validations\Matches;
use orange\dto\attributes\validations\MaxLength;
use orange\dto\attributes\validations\MinLength;

/**
 * Full profile request mirroring the README example, exercising field-name,
 * column, table, label, filter and validation attributes together.
 */
class ProfileRequest extends Dto
{
    #[IsRequired]
    #[MaxLength(64)]
    #[MinLength(1)]
    #[Column('name')]
    #[Table('user')]
    #[ToString]
    #[Label('Name')]
    public string $name;

    #[IsRequired]
    #[ToInteger]
    #[GreaterThan(18)]
    #[LessThan(110)]
    #[Column('age')]
    #[Table('user')]
    #[Label('Age')]
    public int $age;

    #[IsRequired]
    #[MaxLength(16)]
    #[MinLength(4)]
    #[Column('fav_color')]
    #[Table('user')]
    #[FieldName('clr')]
    #[ToString]
    #[Label('Favorite Color')]
    public string $color;
}

/**
 * Dto with a single attributed property and no metadata attributes so the
 * default-to-property-name fallbacks are exercised.
 */
class MinimalRequest extends Dto
{
    #[IsRequired]
    public string $token;

    // A property with no attributes is ignored entirely by the engine.
    public string $ignored = 'untouched';
}

/**
 * Dto whose second field validates against the value of the first,
 * exercising the dto sharing used by comparison validators.
 */
class ConfirmRequest extends Dto
{
    #[IsRequired]
    public string $password;

    #[IsRequired]
    #[Matches('password')]
    #[FieldName('password_confirm')]
    #[Label('Confirmation')]
    public string $confirm;
}

/**
 * A plain PHP attribute that is NOT a DtoAttribute; the engine must ignore it.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class NotADtoAttribute {}

/**
 * Request mixing a non-DtoAttribute attribute with a real validation so the
 * engine's "is this one of ours?" filter is exercised.
 */
class MixedAttributeRequest extends Dto
{
    #[NotADtoAttribute]
    #[IsRequired]
    public string $field;
}

final class DtoTest extends UnitTestHelper
{
    private function validProfileInput(): array
    {
        return [
            'name' => 'Johnny Appleseed',
            'age' => '23',
            'clr' => 'Orange',
        ];
    }

    public function testValidRequestIsValid(): void
    {
        $request = new ProfileRequest($this->validProfileInput());

        $this->assertTrue($request->isValid());
        $this->assertSame([], $request->errors());
    }

    public function testValidRequestAssignsTypedProperties(): void
    {
        $request = new ProfileRequest($this->validProfileInput());

        $this->assertSame('Johnny Appleseed', $request->name);
        $this->assertSame(23, $request->age);
        $this->assertSame('Orange', $request->color);
    }

    public function testAsArrayIsKeyedByPropertyName(): void
    {
        $request = new ProfileRequest($this->validProfileInput());

        $this->assertSame([
            'name' => 'Johnny Appleseed',
            'age' => 23,
            'color' => 'Orange',
        ], $request->asArray());
    }

    public function testAsColumnsIsKeyedByColumnName(): void
    {
        $request = new ProfileRequest($this->validProfileInput());

        $this->assertSame([
            'name' => 'Johnny Appleseed',
            'age' => 23,
            'fav_color' => 'Orange',
        ], $request->asColumns());
    }

    public function testAsTableGroupsByTableName(): void
    {
        $request = new ProfileRequest($this->validProfileInput());

        $this->assertSame([
            'user' => [
                'name' => 'Johnny Appleseed',
                'age' => 23,
                'fav_color' => 'Orange',
            ],
        ], $request->asTable());
    }

    public function testAsTableCanReturnASingleTable(): void
    {
        $request = new ProfileRequest($this->validProfileInput());

        $this->assertSame([
            'name' => 'Johnny Appleseed',
            'age' => 23,
            'fav_color' => 'Orange',
        ], $request->asTable('user'));
    }

    public function testAsTableThrowsForUnknownTable(): void
    {
        $request = new ProfileRequest($this->validProfileInput());

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Table missing not found.');

        $request->asTable('missing');
    }

    public function testInvalidRequestReportsErrorsByFieldName(): void
    {
        $request = new ProfileRequest([
            'name' => '',
            'age' => '10',
            'clr' => 'ab',
        ]);

        $this->assertFalse($request->isValid());

        $errors = $request->errors();

        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('age', $errors);
        // Errors are keyed by the resolved field name, not the property name.
        $this->assertArrayHasKey('clr', $errors);

        $this->assertContains('Name is required', $errors['name']);
        $this->assertContains('Age must be greater than 18', $errors['age']);
        $this->assertContains('Favorite Color must be greater than 4 characters', $errors['clr']);
    }

    public function testInvalidFieldsAreExcludedFromOutput(): void
    {
        $request = new ProfileRequest([
            'name' => '',
            'age' => '10',
            'clr' => 'ab',
        ]);

        $this->assertSame([], $request->asArray());
        $this->assertSame([], $request->asColumns());
        $this->assertSame([], $request->asTable());
    }

    public function testASingleFieldCanFailWhileOthersSucceed(): void
    {
        $request = new ProfileRequest([
            'name' => 'Valid Name',
            'age' => '200',
            'clr' => 'Green',
        ]);

        $this->assertFalse($request->isValid());
        $this->assertArrayHasKey('age', $request->errors());
        $this->assertContains('Age must be less than 110', $request->errors()['age']);

        // The passing fields are still available.
        $this->assertSame(['name' => 'Valid Name', 'color' => 'Green'], $request->asArray());
    }

    public function testDefaultsFallBackToThePropertyName(): void
    {
        $request = new MinimalRequest(['token' => 'abc123']);

        $this->assertTrue($request->isValid());
        $this->assertSame(['token' => 'abc123'], $request->asArray());
        $this->assertSame(['token' => 'abc123'], $request->asColumns());
        $this->assertSame(['token' => ['token' => 'abc123']], $request->asTable());
    }

    public function testPropertiesWithoutAttributesAreIgnored(): void
    {
        $request = new MinimalRequest(['token' => 'abc123']);

        $this->assertArrayNotHasKey('ignored', $request->asArray());
        $this->assertSame('untouched', $request->ignored);
    }

    public function testEmptyRequestProducesEmptyStructures(): void
    {
        $request = new class([]) extends Dto {};

        $this->assertTrue($request->isValid());
        $this->assertSame([], $request->asArray());
        $this->assertSame([], $request->asColumns());
        $this->assertSame([], $request->asTable());
    }

    public function testInputReturnsWholeArrayOrSingleKey(): void
    {
        $input = $this->validProfileInput();
        $request = new ProfileRequest($input);

        $this->assertSame($input, $request->input());
        $this->assertSame('Orange', $request->input('clr'));
        $this->assertSame('', $request->input('missing'));
        $this->assertSame('fallback', $request->input('missing', 'fallback'));
    }

    public function testComparisonValidatorSeesOtherFields(): void
    {
        $valid = new ConfirmRequest([
            'password' => 'secret',
            'password_confirm' => 'secret',
        ]);

        $this->assertTrue($valid->isValid());

        $invalid = new ConfirmRequest([
            'password' => 'secret',
            'password_confirm' => 'different',
        ]);

        $this->assertFalse($invalid->isValid());
        $this->assertContains(
            'Confirmation must match password',
            $invalid->errors()['password_confirm']
        );
    }

    public function testNonDtoAttributesAreIgnored(): void
    {
        $request = new MixedAttributeRequest(['field' => 'present']);

        $this->assertTrue($request->isValid());
        $this->assertSame(['field' => 'present'], $request->asArray());

        $missing = new MixedAttributeRequest(['field' => '']);

        $this->assertFalse($missing->isValid());
        $this->assertArrayHasKey('field', $missing->errors());
    }

    public function testMetadataAccessorsResolveConfiguredValues(): void
    {
        $request = new ProfileRequest($this->validProfileInput());

        // color declares #[FieldName('clr')], #[Column('fav_color')], #[Table('user')], #[Label('Favorite Color')]
        $this->assertSame('clr', $request->fieldName('color'));
        $this->assertSame('fav_color', $request->column('color'));
        $this->assertSame('user', $request->table('color'));
        $this->assertSame('Favorite Color', $request->label('color'));

        // age has a Column/Table/Label but no FieldName.
        $this->assertSame('age', $request->fieldName('age'));
        $this->assertSame('Age', $request->label('age'));
    }

    public function testMetadataAccessorsFallBackToPropertyName(): void
    {
        // token has no metadata attributes at all.
        $request = new MinimalRequest(['token' => 'abc']);

        $this->assertSame('token', $request->fieldName('token'));
        $this->assertSame('token', $request->column('token'));
        $this->assertSame('token', $request->table('token'));
        $this->assertSame('token', $request->label('token'));

        // An unknown property falls back to the given name too.
        $this->assertSame('unknown', $request->fieldName('unknown'));
    }

    public function testValidAndInvalidKeysDefaultToRawPropertyNames(): void
    {
        $valid = new ProfileRequest($this->validProfileInput());

        // By default the keys are the raw property names (color, not clr).
        $this->assertSame(['name', 'age', 'color'], $valid->validKeys());
        $this->assertSame([], $valid->invalidKeys());

        $mixed = new ProfileRequest([
            'name' => 'Valid Name',
            'age' => '200',
            'clr' => 'Green',
        ]);

        $this->assertSame(['name', 'color'], $mixed->validKeys());
        $this->assertSame(['age'], $mixed->invalidKeys());

        $allInvalid = new ProfileRequest([
            'name' => '',
            'age' => '10',
            'clr' => 'ab',
        ]);

        $this->assertSame([], $allInvalid->validKeys());
        $this->assertSame(['name', 'age', 'color'], $allInvalid->invalidKeys());
    }

    public function testValidAndInvalidKeysRawFalseReturnsFieldNames(): void
    {
        $mixed = new ProfileRequest([
            'name' => 'Valid Name',
            'age' => '200',
            'clr' => 'Green',
        ]);

        // $raw = true (default) returns the raw property names (color).
        $this->assertSame(['name', 'color'], $mixed->validKeys(true));
        // $raw = false returns the remapped field names (clr).
        $this->assertSame(['name', 'clr'], $mixed->validKeys(false));

        $this->assertSame(['age'], $mixed->invalidKeys(true));
        $this->assertSame(['age'], $mixed->invalidKeys(false));

        $allInvalid = new ProfileRequest([
            'name' => '',
            'age' => '10',
            'clr' => 'ab',
        ]);

        // The color field is invalid; raw gives 'color', non-raw gives 'clr'.
        $this->assertSame(['name', 'age', 'color'], $allInvalid->invalidKeys(true));
        $this->assertSame(['name', 'age', 'clr'], $allInvalid->invalidKeys(false));
    }

    public function testValidAndInvalidInputKeysReturnFieldNames(): void
    {
        $valid = new ProfileRequest($this->validProfileInput());

        // The *InputKeys() wrappers return the resolved input field names (clr).
        $this->assertSame(['name', 'age', 'clr'], $valid->validInputKeys());
        $this->assertSame([], $valid->invalidInputKeys());

        $mixed = new ProfileRequest([
            'name' => 'Valid Name',
            'age' => '200',
            'clr' => 'Green',
        ]);

        $this->assertSame(['name', 'clr'], $mixed->validInputKeys());
        $this->assertSame(['age'], $mixed->invalidInputKeys());

        $allInvalid = new ProfileRequest([
            'name' => '',
            'age' => '10',
            'clr' => 'ab',
        ]);

        $this->assertSame([], $allInvalid->validInputKeys());
        $this->assertSame(['name', 'age', 'clr'], $allInvalid->invalidInputKeys());
    }

    public function testInputKeyWrappersMatchRawFalse(): void
    {
        $mixed = new ProfileRequest([
            'name' => 'Valid Name',
            'age' => '200',
            'clr' => 'Green',
        ]);

        // The wrappers are equivalent to calling the base methods with $raw = false.
        $this->assertSame($mixed->validKeys(false), $mixed->validInputKeys());
        $this->assertSame($mixed->invalidKeys(false), $mixed->invalidInputKeys());
    }
}
