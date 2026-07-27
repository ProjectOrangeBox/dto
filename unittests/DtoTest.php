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

final class DtoTest extends unitTestHelper
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
        // ProfileRequest names a table, so asColumns() takes its name
        $request = new ProfileRequest($this->validProfileInput());

        $this->assertSame([
            'name' => 'Johnny Appleseed',
            'age' => 23,
            'fav_color' => 'Orange',
        ], $request->asColumns(tablename: 'user'));
    }

    public function testAsColumnsCanBeRestrictedToOneTable(): void
    {
        $request = new ProfileRequest($this->validProfileInput());

        $this->assertSame([
            'name' => 'Johnny Appleseed',
            'age' => 23,
            'fav_color' => 'Orange',
        ], $request->asColumns(tablename: 'user'));
    }

    /**
     * A table the class does not name is a mistyped name or the wrong Dto for
     * the job - a bug either way, and one that is the same for every instance
     * because what a class names is fixed at its first construction.
     */
    public function testAsColumnsThrowsForATableTheClassDoesNotName(): void
    {
        $request = new ProfileRequest($this->validProfileInput());

        // the class's own tables are on the record for comparison
        $this->assertSame(['user'], $request->tables());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('has no table "missing" - it names user');

        $request->asColumns(tablename: 'missing');
    }

    /**
     * A class that names tables describes several, so "every column" is not an
     * answer any one of them can use. There is no sensible default, so leaving
     * the table out is an error rather than a guess.
     */
    public function testTablesReportsWhatTheClassNames(): void
    {
        // in declaration order, each named once however many properties carry it
        $this->assertSame(['records', 'audit'], $this->twoTableRequest()->tables());

        // null is "names none", which is also "asColumns() needs no name"
        $this->assertNull((new MinimalRequest(['token' => 'abc']))->tables());
    }

    /**
     * A declaration, not a reading of the data - an instance that validated
     * nothing still describes the same tables its class does.
     */
    public function testTablesIsUnaffectedByValidation(): void
    {
        $request = new ProfileRequest(['name' => '', 'age' => '10', 'clr' => 'ab']);

        $this->assertFalse($request->isValid());
        $this->assertSame(['user'], $request->tables());
    }

    public function testAsColumnsDemandsATableWhenTheClassNamesAny(): void
    {
        $request = new ProfileRequest($this->validProfileInput());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('names 1 table(s) (user); asColumns() needs to know which one is asking');

        $request->asColumns();
    }

    public function testEachTableGetsOnlyThePropertiesTaggedForIt(): void
    {
        $request = $this->twoTableRequest();

        // one model's share each, and neither is handed the other's column
        $this->assertSame(['name' => 'Don'], $request->asColumns(tablename: 'records'));
        $this->assertSame(['note' => 'hi'], $request->asColumns(tablename: 'audit'));

        // both tables are named, the untagged property's name is not
        $this->assertSame(['records', 'audit'], $request->tables());

        // so no table was invented from it, and asking by it is a mistake
        $this->expectException(\LogicException::class);

        $request->asColumns(tablename: 'scratch');
    }

    public function testAClassThatNamesNoTableTakesAnyTablename(): void
    {
        $request = new MinimalRequest(['token' => 'abc123']);

        $this->assertTrue($request->isValid());

        // it names no table ...
        $this->assertNull($request->tables());

        // ... so it describes whichever one table the model holding it writes
        // to: no name needed, and any name given is simply that one
        $this->assertSame(['token' => 'abc123'], $request->asColumns());
        $this->assertSame(['token' => 'abc123'], $request->asColumns(tablename: 'tokens'));
    }

    public function testABareTableAttributeCountsAsNoTable(): void
    {
        $request = new class(['name' => 'Don']) extends Dto {
            // #[Table]'s name defaults to '' - a table name that says nothing,
            // so it is treated the same as not tagging the property at all
            #[IsRequired]
            #[Table]
            public string $name;
        };

        $this->assertNull($request->table('name'));

        // with nothing usefully tagged, the class names no table at all
        $this->assertNull($request->tables());
        $this->assertSame(['name' => 'Don'], $request->asColumns());
        $this->assertSame(['name' => 'Don'], $request->asColumns(tablename: 'anything'));
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

        // nothing valid landed, so the table it names comes back empty - a
        // reading of the data, not the error an unnamed table would be. The
        // class still reports the table, which is a declaration not data
        $this->assertSame(['user'], $request->tables());
        $this->assertSame([], $request->asColumns(tablename: 'user'));
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

        // ... except the table, which has no property-name fallback: the class
        // names none at all, so it answers to any name asked by
        $this->assertNull($request->tables());
        $this->assertSame(['token' => 'abc123'], $request->asColumns(tablename: 'token'));
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
        $this->assertNull($request->tables());
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
        $this->assertSame('token', $request->label('token'));

        // table() is the exception: no #[Table] means no table, not a table
        // named after the property - naming one would name a table the
        // property is not in, and asColumns() agrees by filing it under none.
        $this->assertNull($request->table('token'));

        // An unknown property falls back to the given name too.
        $this->assertSame('unknown', $request->fieldName('unknown'));
        $this->assertNull($request->table('unknown'));
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

    public function testPrimaryNamesTheColumnNotTheInputField(): void
    {
        $request = new class(['record_id' => 5]) extends Dto {
            #[IsPrimary]
            #[FieldName('record_id')]
            #[ToInteger]
            public int $id;
        };

        // #[FieldName] renames the input key, not the column - primary() names
        // what asColumns() keys by, so the two can never disagree
        $this->assertSame('id', $request->primary());
        $this->assertSame(['id' => 5], $request->primaryValues());
        $this->assertSame(array_keys($request->primaryValues()), $request->primaries());
    }

    public function testPrimaryIsNullWithoutIsPrimary(): void
    {
        $request = new class(['name' => 'Don']) extends Dto {
            #[IsRequired]
            public string $name;
        };

        $this->assertNull($request->primary());
    }

    public function testACompoundKeyKeepsEveryTaggedProperty(): void
    {
        $request = $this->compoundKeyRequest();

        // declaration order, and the keys asColumns() uses
        $this->assertSame(['order_id', 'line_no'], $request->primaries());
        $this->assertSame(['order_id' => 7, 'line_no' => 2], $request->primaryValues());
    }

    /**
     * The singular doors have no answer for a compound key, so they say so
     * rather than naming half of one - which fed to a WHERE would match rows
     * the caller never meant.
     */
    public function testTheSingularPrimaryThrowsOnACompoundKey(): void
    {
        $request = $this->compoundKeyRequest();

        try {
            $request->primary();

            $this->fail('expected LogicException');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('2 primary columns (order_id, line_no)', $e->getMessage());
            $this->assertStringContainsString('use primaries()', $e->getMessage());
        }

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('use primaryValues()');

        $request->primaryValue();
    }

    public function testACompoundKeyGoesWholeUnderWithoutPrimary(): void
    {
        $request = $this->compoundKeyRequest();

        $this->assertSame(['order_id' => 7, 'line_no' => 2, 'sku' => 'A1'], $request->asColumns());

        // both halves go, not just one
        $this->assertSame(['sku' => 'A1'], $request->asColumns(withoutPrimary: true));
    }

    /**
     * One key per table: each model asks for its own and gets an unambiguous
     * answer, while the whole-Dto question stays ambiguous on purpose.
     */
    public function testEachTableCanCarryItsOwnPrimary(): void
    {
        $request = $this->twoTableKeyRequest();

        $this->assertSame(['id'], $request->primaries('users'));
        $this->assertSame(['user_id'], $request->primaries('user_meta'));

        $this->assertSame('id', $request->primary('users'));
        $this->assertSame(7, $request->primaryValue('users'));
        $this->assertSame(['user_id' => 7], $request->primaryValues('user_meta'));

        // unscoped there are two, so the singular doors refuse
        $this->assertSame(['id', 'user_id'], $request->primaries());
        $this->expectException(\LogicException::class);

        $request->primary();
    }

    public function testWithoutPrimaryScopedToATableDropsOnlyThatTablesKey(): void
    {
        $request = $this->twoTableKeyRequest();

        $this->assertSame(['name' => 'Ada'], $request->asColumns(true, 'users'));
        $this->assertSame(['bio' => 'Engineer'], $request->asColumns(true, 'user_meta'));

        // ... and the unflagged shapes keep their own
        $this->assertSame(['id' => 7, 'name' => 'Ada'], $request->asColumns(tablename: 'users'));
        $this->assertSame(['user_id' => 7, 'bio' => 'Engineer'], $request->asColumns(tablename: 'user_meta'));
    }

    /**
     * A class that names no table has only the one it was written for, so a
     * $tablename does not have to match anything for its key to answer.
     */
    public function testATablenameIsIgnoredByAClassThatTagsNoTable(): void
    {
        $request = new class(['id' => '42']) extends Dto {
            #[IsPrimary]
            #[ToInteger]
            public int $id;
        };

        $this->assertSame(['id'], $request->primaries('whatever'));
        $this->assertSame(42, $request->primaryValue('whatever'));
    }

    public function testAPrimaryThatFailedValidationHasNoValue(): void
    {
        $request = new class(['orderId' => 7]) extends Dto {
            #[IsPrimary]
            #[IsRequired]
            #[ToInteger]
            #[Column('order_id')]
            public int $orderId;

            #[IsPrimary]
            #[IsRequired]
            #[ToInteger]
            #[Column('line_no')]
            public int $lineNo;
        };

        $this->assertFalse($request->isValid());

        // the half that validated is there, the half that did not is absent -
        // count it against primaries() to see the key is incomplete
        $this->assertSame(['order_id' => 7], $request->primaryValues());
        $this->assertCount(2, $request->primaries());
    }

    private function twoTableRequest(): Dto
    {
        return new class(['name' => 'Don', 'note' => 'hi', 'scratch' => 'x']) extends Dto {
            #[IsRequired]
            #[Table('records')]
            public string $name;

            #[IsRequired]
            #[Table('audit')]
            public string $note;

            // no #[Table] - belongs to no table in particular
            #[IsRequired]
            public string $scratch;
        };
    }

    private function compoundKeyRequest(): Dto
    {
        return new class(['orderId' => '7', 'lineNo' => '2', 'sku' => 'A1']) extends Dto {
            #[IsPrimary]
            #[ToInteger]
            #[Column('order_id')]
            public int $orderId;

            #[IsPrimary]
            #[ToInteger]
            #[Column('line_no')]
            public int $lineNo;

            #[IsRequired]
            public string $sku;
        };
    }

    private function twoTableKeyRequest(): Dto
    {
        return new class(['id' => '7', 'name' => 'Ada', 'userId' => '7', 'bio' => 'Engineer']) extends Dto {
            #[IsPrimary]
            #[ToInteger]
            #[Column('id')]
            #[Table('users')]
            public int $id;

            #[IsRequired]
            #[Table('users')]
            public string $name;

            #[IsPrimary]
            #[ToInteger]
            #[Column('user_id')]
            #[Table('user_meta')]
            public int $userId;

            #[IsRequired]
            #[Table('user_meta')]
            public string $bio;
        };
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

        $this->assertSame('id', $request->primary());
        $this->assertSame(['id' => 5], $request->asColumns());
        $this->assertSame([], $request->asColumns(withoutPrimary: true));
    }

    public function testAsColumnsWithoutPrimaryIsANoOpWhenNoneIsTagged(): void
    {
        $request = new MinimalRequest(['token' => 'abc']);

        $this->assertSame($request->asColumns(), $request->asColumns(withoutPrimary: true));
    }

    public function testWithoutPrimaryDropsItFromItsOwnTableOnly(): void
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

        // the primary is records', so records loses it ...
        $this->assertSame(['name' => 'Don'], $request->asColumns(true, 'records'));

        // ... and audit, which never had it, is untouched
        $this->assertSame(['note' => 'hi'], $request->asColumns(true, 'audit'));

        // and the unflagged shape keeps it
        $this->assertSame(['id' => 7, 'name' => 'Don'], $request->asColumns(tablename: 'records'));
    }

    public function testDbCastAppliesToDbShapesOnly(): void
    {
        $request = new class(['flag' => true]) extends Dto {
            #[DbCast('int')]
            #[Table('settings')]
            public bool $flag;
        };

        // domain value everywhere the application looks
        $this->assertTrue($request->flag);
        $this->assertSame(['flag' => true], $request->asArray());
        $this->assertSame(json_encode(['flag' => true]), json_encode($request));

        // storage value in the db shape
        $this->assertSame(['flag' => 1], $request->asColumns(tablename: 'settings'));
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
