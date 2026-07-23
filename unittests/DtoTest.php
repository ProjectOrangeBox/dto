<?php

declare(strict_types=1);

use orange\dto\Dto;
use orange\dto\attributes\Column;
use orange\dto\attributes\DbCast;
use orange\dto\attributes\FieldName;
use orange\dto\attributes\IsPrimary;
use orange\dto\attributes\Label;
use orange\dto\attributes\Table;
use orange\dto\attributes\filters\NullIfEmpty;
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

    public function testOptionalFieldSkipsValidationWhenAbsent(): void
    {
        // age carries validations but no presence rule, so leaving it out of
        // the input is not an error — its rules only run on a provided value
        $request = new class(['name' => 'Don']) extends Dto {
            #[IsRequired]
            public string $name;

            #[ToInteger]
            #[GreaterThan(18)]
            public int $age;
        };

        $this->assertTrue($request->isValid());
        $this->assertSame([], $request->errors());
    }

    public function testOptionalFieldStillValidatesWhenProvided(): void
    {
        $request = new class(['name' => 'Don', 'age' => '10']) extends Dto {
            #[IsRequired]
            public string $name;

            #[ToInteger]
            #[GreaterThan(18)]
            public int $age;
        };

        $this->assertFalse($request->isValid());
        $this->assertSame(['age'], $request->invalidInputKeys());
    }

    public function testRequiredFieldStillFailsWhenAbsent(): void
    {
        // presence rules (validatesAbsent()) run even for absent fields
        $request = new class([]) extends Dto {
            #[IsRequired]
            public string $name;
        };

        $this->assertFalse($request->isValid());
        $this->assertArrayHasKey('name', $request->errors());
    }

    public function testPrimaryReturnsColumnNameOfIsPrimaryProperty(): void
    {
        $request = new class(['record_id' => 5]) extends Dto {
            #[IsPrimary]
            #[Column('records_pk')]
            #[FieldName('record_id')]
            #[ToInteger]
            public int $id;
        };

        $this->assertSame('records_pk', $request->primary());
    }

    public function testPrimaryFallsBackToFieldNameWithoutColumn(): void
    {
        $request = new class(['record_id' => 5]) extends Dto {
            #[IsPrimary]
            #[FieldName('record_id')]
            #[ToInteger]
            public int $id;
        };

        $this->assertSame('record_id', $request->primary());
    }

    public function testPrimaryIsNullWithoutIsPrimary(): void
    {
        $request = new class(['name' => 'Don']) extends Dto {
            #[IsRequired]
            public string $name;
        };

        $this->assertNull($request->primary());
    }

    public function testPrimaryLastTaggedPropertyWins(): void
    {
        $request = new class(['a' => 1, 'b' => 2]) extends Dto {
            #[IsPrimary]
            #[ToInteger]
            public int $a;

            #[IsPrimary]
            #[Column('b_pk')]
            #[ToInteger]
            public int $b;
        };

        $this->assertSame('b_pk', $request->primary());
    }

    public function testProtectedSetPropertyIsAssignedByTheEngine(): void
    {
        $request = new class(['name' => 'Don']) extends Dto {
            #[IsRequired]
            public protected(set) string $name;
        };

        $this->assertTrue($request->isValid());
        $this->assertSame('Don', $request->name);
    }

    public function testProtectedSetPropertyRejectsExternalWrites(): void
    {
        $request = new class(['name' => 'Don']) extends Dto {
            #[IsRequired]
            public protected(set) string $name;
        };

        $this->expectException(\Error::class);
        $this->expectExceptionMessageMatches('/Cannot modify protected\(set\) property/');

        $request->name = 'overwritten';
    }

    public function testOnlyReturnsJustTheRequestedProperties(): void
    {
        $request = new ProfileRequest($this->validProfileInput());

        $this->assertSame([
            'name' => 'Johnny Appleseed',
            'color' => 'Orange',
        ], $request->only('name', 'color'));
    }

    public function testOnlySkipsInvalidAndUnknownProperties(): void
    {
        $request = new ProfileRequest(['name' => 'Johnny Appleseed']);

        $this->assertSame(['name' => 'Johnny Appleseed'], $request->only('name', 'age', 'missing'));
    }

    public function testExceptDropsTheGivenProperties(): void
    {
        $request = new ConfirmRequest([
            'password' => 'secret',
            'password_confirm' => 'secret',
        ]);

        $this->assertSame(['password' => 'secret'], $request->except('confirm'));
    }

    public function testPrimaryValueReturnsTheValidatedValue(): void
    {
        $request = new class(['id' => '42']) extends Dto {
            #[IsPrimary]
            #[ToInteger]
            #[FieldName('id')]
            public int $record_id;
        };

        $this->assertSame(42, $request->primaryValue());
    }

    public function testPrimaryValueIsNullWithoutIsPrimary(): void
    {
        $request = new MinimalRequest(['token' => 'abc']);

        $this->assertNull($request->primaryValue());
    }

    public function testPrimaryValueIsNullWhenThePrimaryFailsValidation(): void
    {
        $request = new class([]) extends Dto {
            #[IsPrimary]
            #[IsRequired]
            public string $id;
        };

        $this->assertFalse($request->isValid());
        $this->assertNull($request->primaryValue());
    }

    public function testJsonEncodeEmitsOnlyValidatedData(): void
    {
        $request = new ProfileRequest($this->validProfileInput());

        $this->assertSame(
            json_encode(['name' => 'Johnny Appleseed', 'age' => 23, 'color' => 'Orange']),
            json_encode($request)
        );
    }

    public function testJsonEncodeOmitsInvalidFields(): void
    {
        $request = new ProfileRequest(['name' => 'Johnny Appleseed']);

        $this->assertSame(json_encode(['name' => 'Johnny Appleseed']), json_encode($request));
    }

    public function testJsonEncodeSerializesListsOfDtos(): void
    {
        $requests = [
            new MinimalRequest(['token' => 'first']),
            new MinimalRequest(['token' => 'second']),
        ];

        $this->assertSame(
            json_encode([['token' => 'first'], ['token' => 'second']]),
            json_encode($requests)
        );
    }

    public function testAsColumnsWithoutPrimaryDropsThePrimaryColumn(): void
    {
        $request = new class(['id' => '7', 'name' => 'Don']) extends Dto {
            #[IsPrimary]
            #[ToInteger]
            #[Column('records_pk')]
            public int $id;

            #[IsRequired]
            public string $name;
        };

        $this->assertSame(['records_pk' => 7, 'name' => 'Don'], $request->asColumns());
        $this->assertSame(['name' => 'Don'], $request->asColumns(withoutPrimary: true));
    }

    public function testAsColumnsWithoutPrimaryUsesTheTrueColumnKey(): void
    {
        // without #[Column], primary() falls back to the FieldName while the
        // asColumns() key falls back to the property name — removal must
        // target the real column key, not primary()'s value
        $request = new class(['record_id' => '5']) extends Dto {
            #[IsPrimary]
            #[FieldName('record_id')]
            #[ToInteger]
            public int $id;
        };

        $this->assertSame('record_id', $request->primary());
        $this->assertSame(['id' => 5], $request->asColumns());
        $this->assertSame([], $request->asColumns(withoutPrimary: true));
    }

    public function testAsColumnsWithoutPrimaryIsANoOpWhenNoneIsTagged(): void
    {
        $request = new MinimalRequest(['token' => 'abc']);

        $this->assertSame($request->asColumns(), $request->asColumns(withoutPrimary: true));
    }

    public function testAsTableWithoutPrimaryDropsItFromItsTableOnly(): void
    {
        $request = new class(['id' => '7', 'name' => 'Don', 'note' => 'hi']) extends Dto {
            #[IsPrimary]
            #[ToInteger]
            #[Table('records')]
            public int $id;

            #[IsRequired]
            #[Table('records')]
            public string $name;

            #[IsRequired]
            #[Table('audit')]
            public string $note;
        };

        $this->assertSame(
            ['records' => ['name' => 'Don'], 'audit' => ['note' => 'hi']],
            $request->asTable(withoutPrimary: true)
        );
        $this->assertSame(['name' => 'Don'], $request->asTable('records', true));

        // and the unflagged shape is untouched
        $this->assertSame(['id' => 7, 'name' => 'Don'], $request->asTable('records'));
    }

    public function testDbCastAppliesToDbShapesOnly(): void
    {
        $request = new class(['flag' => true]) extends Dto {
            #[DbCast('int')]
            public bool $flag;
        };

        // domain value everywhere the application looks
        $this->assertTrue($request->flag);
        $this->assertSame(['flag' => true], $request->asArray());
        $this->assertSame(json_encode(['flag' => true]), json_encode($request));

        // storage value in the db shapes
        $this->assertSame(['flag' => 1], $request->asColumns());
        $this->assertSame(['flag' => ['flag' => 1]], $request->asTable());
    }

    public function testDbCastNeverCastsNull(): void
    {
        $request = new class(['when' => null]) extends Dto {
            #[NullIfEmpty]
            #[DbCast('string')]
            public ?string $when = null;
        };

        $this->assertTrue($request->isValid());
        $this->assertNull($request->asColumns()['when']);
    }

    public function testDbCastRejectsAnUnknownTargetAtCompileTime(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("got 'datetime'");

        new class([]) extends Dto {
            #[DbCast('datetime')]
            public string $when;
        };
    }

    public function testDebugInfoCuratesValidityDataAndErrors(): void
    {
        $request = new ProfileRequest(['name' => 'Johnny Appleseed']);

        $debug = $request->__debugInfo();

        $this->assertSame(['valid', 'data', 'errors'], array_keys($debug));
        $this->assertFalse($debug['valid']);
        $this->assertSame(['name' => 'Johnny Appleseed'], $debug['data']);
        $this->assertArrayHasKey('age', $debug['errors']);
        $this->assertArrayHasKey('clr', $debug['errors']);
    }
}
