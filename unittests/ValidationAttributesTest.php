<?php

declare(strict_types=1);

use orange\dto\Dto;
use orange\dto\attributes\validations\Accepted;
use orange\dto\attributes\validations\After;
use orange\dto\attributes\validations\AfterField;
use orange\dto\attributes\validations\Alpha;
use orange\dto\attributes\validations\AlphaDash;
use orange\dto\attributes\validations\AlphaNumeric;
use orange\dto\attributes\validations\AlphaNumericSpaces;
use orange\dto\attributes\validations\Before;
use orange\dto\attributes\validations\BeforeField;
use orange\dto\attributes\validations\Between;
use orange\dto\attributes\validations\BetweenLength;
use orange\dto\attributes\validations\Contains;
use orange\dto\attributes\validations\DateFormat;
use orange\dto\attributes\validations\Decimal;
use orange\dto\attributes\validations\Differs;
use orange\dto\attributes\validations\EndsWith;
use orange\dto\attributes\validations\Equals;
use orange\dto\attributes\validations\ExactLength;
use orange\dto\attributes\validations\GreaterThan;
use orange\dto\attributes\validations\GreaterThanEqualTo;
use orange\dto\attributes\validations\InList;
use orange\dto\attributes\validations\InListEach;
use orange\dto\attributes\validations\Integer as IntegerValidation;
use orange\dto\attributes\validations\IsArray;
use orange\dto\attributes\validations\IsNatural;
use orange\dto\attributes\validations\IsNaturalNoZero;
use orange\dto\attributes\validations\IsRequired;
use orange\dto\attributes\validations\LessThan;
use orange\dto\attributes\validations\LessThanEqualTo;
use orange\dto\attributes\validations\Matches;
use orange\dto\attributes\validations\MaxAge;
use orange\dto\attributes\validations\MaxCount;
use orange\dto\attributes\validations\MaxLength;
use orange\dto\attributes\validations\MinAge;
use orange\dto\attributes\validations\MinCount;
use orange\dto\attributes\validations\MinLength;
use orange\dto\attributes\validations\MultipleOf;
use orange\dto\attributes\validations\NotContains;
use orange\dto\attributes\validations\NotEquals;
use orange\dto\attributes\validations\NotInList;
use orange\dto\attributes\validations\NotRegexMatch;
use orange\dto\attributes\validations\Numeric;
use orange\dto\attributes\validations\ProhibitedIf;
use orange\dto\attributes\validations\ProhibitedWith;
use orange\dto\attributes\validations\RegexMatch;
use orange\dto\attributes\validations\RequiredIf;
use orange\dto\attributes\validations\RequiredUnless;
use orange\dto\attributes\validations\RequiredWith;
use orange\dto\attributes\validations\RequiredWithout;
use orange\dto\attributes\validations\Slug;
use orange\dto\attributes\validations\StartsWith;
use orange\dto\attributes\validations\ValidBase64;
use orange\dto\attributes\validations\ValidCountryCode;
use orange\dto\attributes\validations\ValidCreditCard;
use orange\dto\attributes\validations\ValidCurrencyCode;
use orange\dto\attributes\validations\ValidDate;
use orange\dto\attributes\validations\ValidEmail;
use orange\dto\attributes\validations\ValidEmails;
use orange\dto\attributes\validations\ValidFilename;
use orange\dto\attributes\validations\ValidHexColor;
use orange\dto\attributes\validations\ValidHostname;
use orange\dto\attributes\validations\ValidIban;
use orange\dto\attributes\validations\ValidIp;
use orange\dto\attributes\validations\ValidIsbn;
use orange\dto\attributes\validations\ValidJson;
use orange\dto\attributes\validations\ValidLuhn;
use orange\dto\attributes\validations\ValidMacAddress;
use orange\dto\attributes\validations\ValidPhoneNumber;
use orange\dto\attributes\validations\ValidPort;
use orange\dto\attributes\validations\ValidSemver;
use orange\dto\attributes\validations\ValidTimezone;
use orange\dto\attributes\validations\ValidUlid;
use orange\dto\attributes\validations\ValidUrl;
use orange\dto\attributes\validations\ValidUuid;

final class ValidationAttributesTest extends UnitTestHelper
{
    protected function makeRequest(array $input): Dto
    {
        return new class($input) extends Dto {
        };
    }

    public function testAlpha(): void
    {
        $rule = new Alpha();

        $this->assertTrue($rule->validate('Orange'));
        $this->assertFalse($rule->validate('Orange123'));
        $this->assertEquals('This field may only contain alphabetical characters', $rule->getMessage());
        $this->assertEquals('Name may only contain alphabetical characters', $rule->getMessage('Name'));
    }

    public function testAlphaDash(): void
    {
        $rule = new AlphaDash();

        $this->assertTrue($rule->validate('Orange-Test'));
        $this->assertFalse($rule->validate('Orange_Test'));
        $this->assertFalse($rule->validate('Orange_123-Test'));
        $this->assertFalse($rule->validate('Orange Test'));
        $this->assertEquals('This field may only contain alpha-numeric characters, underscores, and dashes', $rule->getMessage());
        $this->assertEquals('Slug may only contain alpha-numeric characters, underscores, and dashes', $rule->getMessage('Slug'));
    }

    public function testAlphaNumeric(): void
    {
        $rule = new AlphaNumeric();

        $this->assertTrue($rule->validate('Orange123'));
        $this->assertFalse($rule->validate('Orange-123'));
        $this->assertEquals('This field may only contain alpha-numeric characters', $rule->getMessage());
        $this->assertEquals('Code may only contain alpha-numeric characters', $rule->getMessage('Code'));
    }

    public function testAlphaNumericSpaces(): void
    {
        $rule = new AlphaNumericSpaces();

        $this->assertTrue($rule->validate('Orange 123'));
        $this->assertFalse($rule->validate('Orange-123'));
        $this->assertEquals('This field may only contain alpha-numeric characters and spaces', $rule->getMessage());
        $this->assertEquals('Title may only contain alpha-numeric characters and spaces', $rule->getMessage('Title'));
    }

    public function testDecimal(): void
    {
        $rule = new Decimal();

        $this->assertTrue($rule->validate('10.25'));
        $this->assertFalse($rule->validate('10'));
        $this->assertEquals('This field must contain a decimal number', $rule->getMessage());
        $this->assertEquals('Price must contain a decimal number', $rule->getMessage('Price'));
    }

    public function testDiffers(): void
    {
        $rule = new Differs('password');
        $rule->request($this->makeRequest(['password' => 'secret']));

        $this->assertTrue($rule->validate('different'));
        $this->assertFalse($rule->validate('secret'));
        $this->assertEquals('This field must differ from password', $rule->getMessage());
        $this->assertEquals('Confirm Password must differ from password', $rule->getMessage('Confirm Password'));
        $this->assertEquals('password', $rule->getField());
    }

    public function testExactLength(): void
    {
        $rule = new ExactLength(5);

        $this->assertTrue($rule->validate('Apple'));
        $this->assertFalse($rule->validate('Pear'));
        $this->assertEquals(5, $rule->getLength());
        $this->assertEquals('This field must be exactly 5 characters', $rule->getMessage());
        $this->assertEquals('Pin must be exactly 5 characters', $rule->getMessage('Pin'));
    }

    public function testGreaterThan(): void
    {
        $rule = new GreaterThan(10);

        $this->assertTrue($rule->validate(11));
        $this->assertTrue($rule->validate('11'));
        $this->assertFalse($rule->validate(10));
        $this->assertFalse($rule->validate('abc'));
        $this->assertEquals(10, $rule->getValue());
        $this->assertEquals('This field must be greater than 10', $rule->getMessage());
        $this->assertEquals('Count must be greater than 10', $rule->getMessage('Count'));
    }

    public function testGreaterThanAcceptsFloatThreshold(): void
    {
        $rule = new GreaterThan(19.99);

        $this->assertTrue($rule->validate(20));
        $this->assertTrue($rule->validate('19.995'));
        $this->assertFalse($rule->validate(19.99));
        $this->assertFalse($rule->validate('19.98'));
        $this->assertEquals(19.99, $rule->getValue());
        $this->assertEquals('This field must be greater than 19.99', $rule->getMessage());
    }

    public function testGreaterThanEqualTo(): void
    {
        $rule = new GreaterThanEqualTo(10);

        $this->assertTrue($rule->validate(10));
        $this->assertTrue($rule->validate('11'));
        $this->assertFalse($rule->validate(9));
        $this->assertEquals(10, $rule->getValue());
        $this->assertEquals('This field must be greater than or equal to 10', $rule->getMessage());
        $this->assertEquals('Count must be greater than or equal to 10', $rule->getMessage('Count'));
    }

    public function testInList(): void
    {
        $rule = new InList(['draft', 'published']);

        $this->assertTrue($rule->validate('draft'));
        $this->assertFalse($rule->validate('archived'));
        $this->assertEquals(['draft', 'published'], $rule->getValues());
        $this->assertEquals('This field must be one of the allowed values', $rule->getMessage());
        $this->assertEquals('Status must be one of the allowed values', $rule->getMessage('Status'));
    }

    public function testInteger(): void
    {
        $rule = new IntegerValidation();

        $this->assertTrue($rule->validate('10'));
        $this->assertTrue($rule->validate(-5));
        $this->assertFalse($rule->validate('10.5'));
        $this->assertEquals('This field must contain an integer', $rule->getMessage());
        $this->assertEquals('Age must contain an integer', $rule->getMessage('Age'));
    }

    public function testIsNatural(): void
    {
        $rule = new IsNatural();

        $this->assertTrue($rule->validate('0'));
        $this->assertTrue($rule->validate('25'));
        $this->assertFalse($rule->validate('-1'));
        $this->assertEquals('This field must contain only natural numbers', $rule->getMessage());
        $this->assertEquals('Count must contain only natural numbers', $rule->getMessage('Count'));
    }

    public function testIsNaturalNoZero(): void
    {
        $rule = new IsNaturalNoZero();

        $this->assertTrue($rule->validate('25'));
        $this->assertFalse($rule->validate('0'));
        $this->assertEquals('This field must contain a natural number greater than zero', $rule->getMessage());
        $this->assertEquals('Count must contain a natural number greater than zero', $rule->getMessage('Count'));
    }

    public function testIsRequired(): void
    {
        $rule = new IsRequired();

        $this->assertTrue($rule->validate('filled'));
        // '0' and 0 are meaningful values, not "empty" (unlike PHP's empty()).
        $this->assertTrue($rule->validate('0'));
        $this->assertTrue($rule->validate(0));
        $this->assertFalse($rule->validate(''));
        $this->assertFalse($rule->validate(null));
        $this->assertFalse($rule->validate([]));
        $this->assertEquals('This field is required', $rule->getMessage());
        $this->assertEquals('Name is required', $rule->getMessage('Name'));
    }

    public function testLessThan(): void
    {
        $rule = new LessThan(10);

        $this->assertTrue($rule->validate(9));
        $this->assertTrue($rule->validate('9'));
        $this->assertFalse($rule->validate(10));
        $this->assertFalse($rule->validate('abc'));
        $this->assertEquals(10, $rule->getValue());
        $this->assertEquals('This field must be less than 10', $rule->getMessage());
        $this->assertEquals('Count must be less than 10', $rule->getMessage('Count'));
    }

    public function testLessThanAcceptsFloatThreshold(): void
    {
        $rule = new LessThan(0.5);

        $this->assertTrue($rule->validate(0.49));
        $this->assertFalse($rule->validate(0.5));
        $this->assertFalse($rule->validate(0.51));
        $this->assertEquals(0.5, $rule->getValue());
        $this->assertEquals('This field must be less than 0.5', $rule->getMessage());
    }

    public function testLessThanEqualTo(): void
    {
        $rule = new LessThanEqualTo(10);

        $this->assertTrue($rule->validate(10));
        $this->assertTrue($rule->validate('9'));
        $this->assertFalse($rule->validate(11));
        $this->assertEquals(10, $rule->getValue());
        $this->assertEquals('This field must be less than or equal to 10', $rule->getMessage());
        $this->assertEquals('Count must be less than or equal to 10', $rule->getMessage('Count'));
    }

    public function testMatches(): void
    {
        $rule = new Matches('password');
        $rule->request($this->makeRequest(['password' => 'secret']));

        $this->assertTrue($rule->validate('secret'));
        $this->assertFalse($rule->validate('different'));
        $this->assertEquals('This field must match password', $rule->getMessage());
        $this->assertEquals('Confirm Password must match password', $rule->getMessage('Confirm Password'));
        $this->assertEquals('password', $rule->getField());
    }

    public function testMaxLength(): void
    {
        $rule = new MaxLength(6);

        $this->assertTrue($rule->validate('Apple'));
        $this->assertFalse($rule->validate('Oranges'));
        $this->assertEquals(6, $rule->getLength());
        $this->assertEquals('This field must be less than 6 characters', $rule->getMessage());
        $this->assertEquals('Name must be less than 6 characters', $rule->getMessage('Name'));
    }

    public function testMinLength(): void
    {
        $rule = new MinLength(3);

        $this->assertTrue($rule->validate('Pear'));
        $this->assertFalse($rule->validate('Fig'));
        $this->assertEquals(3, $rule->getLength());
        $this->assertEquals('This field must be greater than 3 characters', $rule->getMessage());
        $this->assertEquals('Name must be greater than 3 characters', $rule->getMessage('Name'));
    }

    public function testNumeric(): void
    {
        $rule = new Numeric();

        $this->assertTrue($rule->validate('10'));
        $this->assertTrue($rule->validate('10.5'));
        $this->assertFalse($rule->validate('ten'));
        $this->assertEquals('This field must contain only numbers', $rule->getMessage());
        $this->assertEquals('Price must contain only numbers', $rule->getMessage('Price'));
    }

    public function testRegexMatch(): void
    {
        $rule = new RegexMatch('/^[A-Z]{3}[0-9]{3}$/');

        $this->assertTrue($rule->validate('ABC123'));
        $this->assertFalse($rule->validate('abc123'));
        $this->assertEquals('/^[A-Z]{3}[0-9]{3}$/', $rule->getPattern());
        $this->assertEquals('This field is not in the correct format', $rule->getMessage());
        $this->assertEquals('Code is not in the correct format', $rule->getMessage('Code'));
    }

    public function testValidBase64(): void
    {
        $rule = new ValidBase64();

        $this->assertTrue($rule->validate(base64_encode('orange')));
        $this->assertFalse($rule->validate('not-base64'));
        $this->assertEquals('This field must contain a valid base64 string', $rule->getMessage());
        $this->assertEquals('Payload must contain a valid base64 string', $rule->getMessage('Payload'));
    }

    public function testValidEmail(): void
    {
        $rule = new ValidEmail();

        $this->assertTrue($rule->validate('test@example.com'));
        $this->assertFalse($rule->validate('not-an-email'));
        $this->assertEquals('This field must contain a valid email address', $rule->getMessage());
        $this->assertEquals('Email must contain a valid email address', $rule->getMessage('Email'));
    }

    public function testValidEmails(): void
    {
        $rule = new ValidEmails();

        $this->assertTrue($rule->validate('one@example.com, two@example.com'));
        $this->assertFalse($rule->validate('one@example.com, invalid'));
        // A trailing comma yields an empty segment, which is rejected.
        $this->assertFalse($rule->validate('one@example.com,'));
        $this->assertEquals('This field must contain only valid email addresses', $rule->getMessage());
        $this->assertEquals('Recipients must contain only valid email addresses', $rule->getMessage('Recipients'));
    }

    public function testValidIp(): void
    {
        $rule = new ValidIp();
        $ipv4Rule = new ValidIp('ipv4');
        $ipv6Rule = new ValidIp('ipv6');

        $this->assertTrue($rule->validate('127.0.0.1'));
        $this->assertTrue($ipv4Rule->validate('127.0.0.1'));
        $this->assertTrue($ipv6Rule->validate('2001:0db8:85a3:0000:0000:8a2e:0370:7334'));
        $this->assertFalse($ipv4Rule->validate('2001:0db8:85a3:0000:0000:8a2e:0370:7334'));
        $this->assertEquals('ipv4', $ipv4Rule->getVersion());
        $this->assertEquals('This field must contain a valid IP address', $rule->getMessage());
        $this->assertEquals('Address must contain a valid IP address', $rule->getMessage('Address'));
    }

    public function testValidUrl(): void
    {
        $rule = new ValidUrl();

        $this->assertTrue($rule->validate('https://example.com/path'));
        $this->assertFalse($rule->validate('not-a-url'));
        $this->assertEquals('This field must contain a valid URL', $rule->getMessage());
        $this->assertEquals('Website must contain a valid URL', $rule->getMessage('Website'));
    }

    public function testValidDate(): void
    {
        $rule = new ValidDate();

        $this->assertTrue($rule->validate('2026-07-17'));
        $this->assertTrue($rule->validate('17 July 2026'));
        $this->assertFalse($rule->validate('not a date'));
        $this->assertFalse($rule->validate(''));
        $this->assertEquals('This field must contain a valid date', $rule->getMessage());
        $this->assertEquals('Birthday must contain a valid date', $rule->getMessage('Birthday'));
    }

    public function testDateFormat(): void
    {
        $rule = new DateFormat('Y-m-d');

        $this->assertTrue($rule->validate('2026-07-17'));
        // Wrong format is rejected.
        $this->assertFalse($rule->validate('07/17/2026'));
        // Overflow dates are rejected by the round-trip check.
        $this->assertFalse($rule->validate('2026-13-45'));
        $this->assertEquals('Y-m-d', $rule->getFormat());
        $this->assertEquals('This field must be a valid date in the format Y-m-d', $rule->getMessage());
        $this->assertEquals('Start must be a valid date in the format Y-m-d', $rule->getMessage('Start'));
    }

    public function testBetween(): void
    {
        $rule = new Between(1, 10);

        $this->assertTrue($rule->validate(1));
        $this->assertTrue($rule->validate('5'));
        $this->assertTrue($rule->validate(10));
        $this->assertFalse($rule->validate(0));
        $this->assertFalse($rule->validate(11));
        $this->assertFalse($rule->validate('abc'));
        $this->assertEquals(1, $rule->getMin());
        $this->assertEquals(10, $rule->getMax());
        $this->assertEquals('This field must be between 1 and 10', $rule->getMessage());
        $this->assertEquals('Rating must be between 1 and 10', $rule->getMessage('Rating'));
    }

    public function testBetweenLength(): void
    {
        $rule = new BetweenLength(2, 5);

        $this->assertTrue($rule->validate('ab'));
        $this->assertTrue($rule->validate('abcde'));
        $this->assertFalse($rule->validate('a'));
        $this->assertFalse($rule->validate('abcdef'));
        $this->assertEquals(2, $rule->getMin());
        $this->assertEquals(5, $rule->getMax());
        $this->assertEquals('This field must be between 2 and 5 characters', $rule->getMessage());
        $this->assertEquals('Code must be between 2 and 5 characters', $rule->getMessage('Code'));
    }

    public function testValidJson(): void
    {
        $rule = new ValidJson();

        $this->assertTrue($rule->validate('{"a":1}'));
        $this->assertTrue($rule->validate('[1,2,3]'));
        $this->assertFalse($rule->validate('{invalid}'));
        $this->assertFalse($rule->validate(''));
        $this->assertEquals('This field must contain valid JSON', $rule->getMessage());
        $this->assertEquals('Payload must contain valid JSON', $rule->getMessage('Payload'));
    }

    public function testValidUuid(): void
    {
        $rule = new ValidUuid();

        $this->assertTrue($rule->validate('550e8400-e29b-41d4-a716-446655440000'));
        $this->assertTrue($rule->validate('018F3B7C-7B7C-7B7C-8B7C-7B7C7B7C7B7C'));
        $this->assertFalse($rule->validate('not-a-uuid'));
        $this->assertFalse($rule->validate('550e8400e29b41d4a716446655440000'));
        $this->assertEquals('This field must contain a valid UUID', $rule->getMessage());
        $this->assertEquals('Id must contain a valid UUID', $rule->getMessage('Id'));
    }

    public function testNotInList(): void
    {
        $rule = new NotInList(['admin', 'root']);

        $this->assertTrue($rule->validate('editor'));
        $this->assertFalse($rule->validate('admin'));
        $this->assertEquals(['admin', 'root'], $rule->getValues());
        $this->assertEquals('This field must not be one of the disallowed values', $rule->getMessage());
        $this->assertEquals('Username must not be one of the disallowed values', $rule->getMessage('Username'));
    }

    public function testStartsWith(): void
    {
        $rule = new StartsWith('ORD-');

        $this->assertTrue($rule->validate('ORD-123'));
        $this->assertFalse($rule->validate('INV-123'));
        $this->assertEquals('ORD-', $rule->getNeedle());
        $this->assertEquals('This field must start with ORD-', $rule->getMessage());
        $this->assertEquals('Reference must start with ORD-', $rule->getMessage('Reference'));
    }

    public function testEndsWith(): void
    {
        $rule = new EndsWith('.pdf');

        $this->assertTrue($rule->validate('report.pdf'));
        $this->assertFalse($rule->validate('report.txt'));
        $this->assertEquals('.pdf', $rule->getNeedle());
        $this->assertEquals('This field must end with .pdf', $rule->getMessage());
        $this->assertEquals('Filename must end with .pdf', $rule->getMessage('Filename'));
    }

    public function testContains(): void
    {
        $rule = new Contains('@');

        $this->assertTrue($rule->validate('user@example.com'));
        $this->assertFalse($rule->validate('userexample.com'));
        $this->assertEquals('@', $rule->getNeedle());
        $this->assertEquals('This field must contain @', $rule->getMessage());
        $this->assertEquals('Handle must contain @', $rule->getMessage('Handle'));
    }

    public function testRequiredIf(): void
    {
        $rule = new RequiredIf('type', 'other');
        $rule->request($this->makeRequest(['type' => 'other']));

        // The trigger value is present, so the field is required.
        $this->assertTrue($rule->validate('filled'));
        // '0' is a meaningful value, not "empty".
        $this->assertTrue($rule->validate('0'));
        $this->assertFalse($rule->validate(''));
        $this->assertEquals('type', $rule->getField());
        $this->assertEquals('other', $rule->getValue());
        $this->assertEquals('This field is required', $rule->getMessage());

        // The trigger value is absent, so the field is optional.
        $optional = new RequiredIf('type', 'other');
        $optional->request($this->makeRequest(['type' => 'standard']));
        $this->assertTrue($optional->validate(''));
    }

    public function testRequiredWith(): void
    {
        $rule = new RequiredWith('shipping');
        $rule->request($this->makeRequest(['shipping' => 'express']));

        // The companion field has a value, so this field is required.
        $this->assertTrue($rule->validate('123 Main St'));
        // '0' is a meaningful value, not "empty".
        $this->assertTrue($rule->validate('0'));
        $this->assertFalse($rule->validate(''));
        $this->assertEquals('shipping', $rule->getField());
        $this->assertEquals('This field is required', $rule->getMessage());

        // The companion field is empty, so this field is optional.
        $optional = new RequiredWith('shipping');
        $optional->request($this->makeRequest(['shipping' => '']));
        $this->assertTrue($optional->validate(''));

        // A companion field whose value is '0' still counts as "has a value".
        $zeroTriggers = new RequiredWith('shipping');
        $zeroTriggers->request($this->makeRequest(['shipping' => '0']));
        $this->assertFalse($zeroTriggers->validate(''));
    }

    public function testValidCreditCard(): void
    {
        $rule = new ValidCreditCard();

        // Well-known Visa test number that satisfies the Luhn checksum.
        $this->assertTrue($rule->validate('4111111111111111'));
        $this->assertTrue($rule->validate('4111 1111 1111 1111'));
        $this->assertFalse($rule->validate('4111111111111112'));
        // Too short even though it would pass Luhn.
        $this->assertFalse($rule->validate('0'));
        $this->assertEquals('This field must contain a valid credit card number', $rule->getMessage());
        $this->assertEquals('Card must contain a valid credit card number', $rule->getMessage('Card'));
    }

    public function testValidTimezone(): void
    {
        $rule = new ValidTimezone();

        $this->assertTrue($rule->validate('America/New_York'));
        $this->assertTrue($rule->validate('UTC'));
        $this->assertFalse($rule->validate('Mars/Phobos'));
        $this->assertEquals('This field must contain a valid timezone', $rule->getMessage());
        $this->assertEquals('Zone must contain a valid timezone', $rule->getMessage('Zone'));
    }

    public function testValidHostname(): void
    {
        $rule = new ValidHostname();

        $this->assertTrue($rule->validate('example.com'));
        $this->assertTrue($rule->validate('sub.example.co.uk'));
        $this->assertFalse($rule->validate('not a hostname'));
        $this->assertFalse($rule->validate(''));
        $this->assertEquals('This field must contain a valid hostname', $rule->getMessage());
        $this->assertEquals('Host must contain a valid hostname', $rule->getMessage('Host'));
    }

    public function testSlug(): void
    {
        $rule = new Slug();

        $this->assertTrue($rule->validate('my-first-post'));
        $this->assertTrue($rule->validate('post123'));
        $this->assertFalse($rule->validate('My-First-Post'));
        $this->assertFalse($rule->validate('-leading'));
        $this->assertFalse($rule->validate('double--dash'));
        $this->assertEquals('This field must be a valid slug', $rule->getMessage());
        $this->assertEquals('Slug must be a valid slug', $rule->getMessage('Slug'));
    }

    public function testValidHexColor(): void
    {
        $rule = new ValidHexColor();

        $this->assertTrue($rule->validate('#fff'));
        $this->assertTrue($rule->validate('#FF8800'));
        $this->assertTrue($rule->validate('abc123'));
        $this->assertFalse($rule->validate('#ff'));
        $this->assertFalse($rule->validate('#ggg'));
        $this->assertEquals('This field must be a valid hex color', $rule->getMessage());
        $this->assertEquals('Background must be a valid hex color', $rule->getMessage('Background'));
    }

    /**
     * Every validator with a type guard must reject a non-scalar value (an array)
     * rather than throwing, exercising the guard's false branch.
     */
    public function testNonScalarInputIsRejectedByGuardedValidators(): void
    {
        $rules = [
            new Accepted(),
            new After('now'),
            new AfterField('other'),
            new Alpha(),
            new AlphaDash(),
            new AlphaNumeric(),
            new AlphaNumericSpaces(),
            new Before('now'),
            new BeforeField('other'),
            new Between(1, 10),
            new BetweenLength(1, 10),
            new Contains('a'),
            new DateFormat('Y-m-d'),
            new Decimal(),
            new EndsWith('a'),
            new Equals('a'),
            new ExactLength(5),
            new GreaterThan(10),
            new GreaterThanEqualTo(10),
            new InList(['a', 'b']),
            new IntegerValidation(),
            new IsNatural(),
            new IsNaturalNoZero(),
            new LessThan(10),
            new LessThanEqualTo(10),
            new MaxLength(6),
            new MinLength(3),
            new MultipleOf(5),
            new NotEquals('a'),
            new NotInList(['a', 'b']),
            new Numeric(),
            new RegexMatch('/^[a-z]+$/'),
            new Slug(),
            new StartsWith('a'),
            new ValidBase64(),
            new ValidCountryCode(),
            new ValidCreditCard(),
            new ValidCurrencyCode(),
            new ValidDate(),
            new ValidEmail(),
            new ValidEmails(),
            new ValidHexColor(),
            new ValidHostname(),
            new ValidIp(),
            new ValidJson(),
            new ValidPhoneNumber(),
            new ValidTimezone(),
            new ValidUrl(),
            new ValidUuid(),
        ];

        foreach ($rules as $rule) {
            $this->assertFalse(
                $rule->validate(['not', 'scalar']),
                get_class($rule) . ' should reject a non-scalar array'
            );
        }
    }

    public function testValidEmailsRejectsEmptyString(): void
    {
        $rule = new ValidEmails();

        $this->assertFalse($rule->validate(''));
    }

    public function testValidIpRejectsInvalidAddress(): void
    {
        $rule = new ValidIp();

        $this->assertFalse($rule->validate('999.999.999.999'));
        $this->assertEquals('', $rule->getVersion());
    }

    public function testEquals(): void
    {
        $rule = new Equals('draft');

        $this->assertTrue($rule->validate('draft'));
        // Compared as strings, so the int 5 matches the string '5'.
        $intRule = new Equals(5);
        $this->assertTrue($intRule->validate('5'));
        $this->assertFalse($rule->validate('published'));
        $this->assertEquals('draft', $rule->getValue());
        $this->assertEquals('This field must equal draft', $rule->getMessage());
        $this->assertEquals('Status must equal draft', $rule->getMessage('Status'));
    }

    public function testNotEquals(): void
    {
        $rule = new NotEquals('admin');

        $this->assertTrue($rule->validate('editor'));
        $this->assertFalse($rule->validate('admin'));
        $this->assertEquals('admin', $rule->getValue());
        $this->assertEquals('This field must not equal admin', $rule->getMessage());
        $this->assertEquals('Username must not equal admin', $rule->getMessage('Username'));
    }

    public function testAccepted(): void
    {
        $rule = new Accepted();

        $this->assertTrue($rule->validate(true));
        $this->assertTrue($rule->validate(1));
        $this->assertTrue($rule->validate('1'));
        $this->assertTrue($rule->validate('yes'));
        $this->assertTrue($rule->validate('on'));
        $this->assertTrue($rule->validate('TRUE'));
        $this->assertFalse($rule->validate(false));
        $this->assertFalse($rule->validate(0));
        $this->assertFalse($rule->validate('no'));
        $this->assertFalse($rule->validate(''));
        $this->assertEquals('This field must be accepted', $rule->getMessage());
        $this->assertEquals('Terms must be accepted', $rule->getMessage('Terms'));
    }

    public function testMultipleOf(): void
    {
        $rule = new MultipleOf(5);

        $this->assertTrue($rule->validate(10));
        $this->assertTrue($rule->validate('15'));
        $this->assertTrue($rule->validate(0));
        $this->assertFalse($rule->validate(12));
        $this->assertFalse($rule->validate('abc'));

        // Float steps tolerate normal floating point rounding error.
        $floatRule = new MultipleOf(0.1);
        $this->assertTrue($floatRule->validate(0.3));

        $this->assertEquals(5, $rule->getStep());
        $this->assertEquals('This field must be a multiple of 5', $rule->getMessage());
        $this->assertEquals('Quantity must be a multiple of 5', $rule->getMessage('Quantity'));
    }

    public function testBefore(): void
    {
        $rule = new Before('2030-01-01');

        $this->assertTrue($rule->validate('2029-12-31'));
        $this->assertFalse($rule->validate('2030-01-01'));
        $this->assertFalse($rule->validate('2030-06-15'));
        $this->assertFalse($rule->validate('not a date'));
        $this->assertEquals('2030-01-01', $rule->getDate());
        $this->assertEquals('This field must be before 2030-01-01', $rule->getMessage());

        // 'now' is resolved dynamically via strtotime().
        $pastRule = new Before('now');
        $this->assertTrue($pastRule->validate('2000-01-01'));
        $this->assertFalse($pastRule->validate('2999-01-01'));
    }

    public function testAfter(): void
    {
        $rule = new After('2000-01-01');

        $this->assertTrue($rule->validate('2000-01-02'));
        $this->assertFalse($rule->validate('2000-01-01'));
        $this->assertFalse($rule->validate('1999-12-31'));
        $this->assertFalse($rule->validate('not a date'));
        $this->assertEquals('2000-01-01', $rule->getDate());
        $this->assertEquals('This field must be after 2000-01-01', $rule->getMessage());

        $futureRule = new After('now');
        $this->assertTrue($futureRule->validate('2999-01-01'));
        $this->assertFalse($futureRule->validate('2000-01-01'));
    }

    public function testBeforeField(): void
    {
        $rule = new BeforeField('end_date');
        $rule->request($this->makeRequest(['end_date' => '2026-12-31']));

        $this->assertTrue($rule->validate('2026-01-01'));
        $this->assertFalse($rule->validate('2026-12-31'));
        $this->assertFalse($rule->validate('2027-01-01'));
        $this->assertEquals('end_date', $rule->getField());
        $this->assertEquals('This field must be before end_date', $rule->getMessage());
        $this->assertEquals('Start Date must be before end_date', $rule->getMessage('Start Date'));
    }

    public function testAfterField(): void
    {
        $rule = new AfterField('start_date');
        $rule->request($this->makeRequest(['start_date' => '2026-01-01']));

        $this->assertTrue($rule->validate('2026-12-31'));
        $this->assertFalse($rule->validate('2026-01-01'));
        $this->assertFalse($rule->validate('2025-01-01'));
        $this->assertEquals('start_date', $rule->getField());
        $this->assertEquals('This field must be after start_date', $rule->getMessage());
        $this->assertEquals('End Date must be after start_date', $rule->getMessage('End Date'));
    }

    public function testValidPhoneNumber(): void
    {
        $rule = new ValidPhoneNumber();

        $this->assertTrue($rule->validate('+1 555-123-4567'));
        $this->assertTrue($rule->validate('(555) 123-4567'));
        $this->assertTrue($rule->validate('5551234567'));
        $this->assertFalse($rule->validate('123'));
        $this->assertFalse($rule->validate('abc'));
        $this->assertFalse($rule->validate(''));
        $this->assertEquals('This field must contain a valid phone number', $rule->getMessage());
        $this->assertEquals('Phone must contain a valid phone number', $rule->getMessage('Phone'));
    }

    public function testValidCountryCode(): void
    {
        $rule = new ValidCountryCode();

        $this->assertTrue($rule->validate('US'));
        $this->assertTrue($rule->validate('gb'));
        $this->assertFalse($rule->validate('ZZ'));
        $this->assertFalse($rule->validate(''));
        $this->assertEquals('This field must be a valid country code', $rule->getMessage());
        $this->assertEquals('Country must be a valid country code', $rule->getMessage('Country'));
    }

    public function testValidCurrencyCode(): void
    {
        $rule = new ValidCurrencyCode();

        $this->assertTrue($rule->validate('USD'));
        $this->assertTrue($rule->validate('eur'));
        $this->assertFalse($rule->validate('ZZZ'));
        $this->assertFalse($rule->validate(''));
        $this->assertEquals('This field must be a valid currency code', $rule->getMessage());
        $this->assertEquals('Currency must be a valid currency code', $rule->getMessage('Currency'));
    }

    public function testRequiredUnless(): void
    {
        $rule = new RequiredUnless('type', 'guest');
        $rule->request($this->makeRequest(['type' => 'member']));

        // The exempting value is absent, so the field is required.
        $this->assertTrue($rule->validate('filled'));
        $this->assertFalse($rule->validate(''));
        $this->assertEquals('type', $rule->getField());
        $this->assertEquals('guest', $rule->getValue());
        $this->assertEquals('This field is required', $rule->getMessage());

        // The exempting value matches, so the field is optional.
        $optional = new RequiredUnless('type', 'guest');
        $optional->request($this->makeRequest(['type' => 'guest']));
        $this->assertTrue($optional->validate(''));
    }

    public function testRequiredWithout(): void
    {
        $rule = new RequiredWithout('email');
        $rule->request($this->makeRequest([]));

        // The companion field is empty, so this field is required.
        $this->assertTrue($rule->validate('555-1234'));
        $this->assertFalse($rule->validate(''));
        $this->assertEquals('email', $rule->getField());
        $this->assertEquals('This field is required', $rule->getMessage());

        // The companion field has a value, so this field is optional.
        $optional = new RequiredWithout('email');
        $optional->request($this->makeRequest(['email' => 'don@example.com']));
        $this->assertTrue($optional->validate(''));
    }

    public function testProhibitedIf(): void
    {
        $rule = new ProhibitedIf('type', 'guest');
        $rule->request($this->makeRequest(['type' => 'guest']));

        // The prohibiting value matches, so the field must be empty.
        $this->assertTrue($rule->validate(''));
        $this->assertFalse($rule->validate('filled'));
        $this->assertEquals('type', $rule->getField());
        $this->assertEquals('guest', $rule->getValue());
        $this->assertEquals('This field must be empty', $rule->getMessage());

        // The prohibiting value is absent, so the field may be filled.
        $allowed = new ProhibitedIf('type', 'guest');
        $allowed->request($this->makeRequest(['type' => 'member']));
        $this->assertTrue($allowed->validate('filled'));
    }

    public function testProhibitedWith(): void
    {
        $rule = new ProhibitedWith('card_number');
        $rule->request($this->makeRequest(['card_number' => '4111']));

        // The companion field is filled, so this field must be empty.
        $this->assertTrue($rule->validate(''));
        $this->assertFalse($rule->validate('paypal@example.com'));
        $this->assertEquals('card_number', $rule->getField());
        $this->assertEquals('This field must be empty', $rule->getMessage());

        // The companion field is empty, so this field may be filled.
        $allowed = new ProhibitedWith('card_number');
        $allowed->request($this->makeRequest([]));
        $this->assertTrue($allowed->validate('paypal@example.com'));
    }

    public function testNotRegexMatch(): void
    {
        $rule = new NotRegexMatch('/^admin/i');

        $this->assertTrue($rule->validate('donmyers'));
        $this->assertFalse($rule->validate('admin2'));
        $this->assertFalse($rule->validate('ADMINuser'));
        $this->assertEquals('/^admin/i', $rule->getPattern());
        $this->assertEquals('This field is not in an allowed format', $rule->getMessage());
    }

    public function testNotContains(): void
    {
        $rule = new NotContains(' ');

        $this->assertTrue($rule->validate('no-spaces-here'));
        $this->assertFalse($rule->validate('has a space'));
        $this->assertEquals(' ', $rule->getNeedle());
        $this->assertEquals('This field must not contain  ', $rule->getMessage());
    }

    public function testValidUlid(): void
    {
        $rule = new ValidUlid();

        $this->assertTrue($rule->validate('01ARZ3NDEKTSV4RRFFQ69G5FAV'));
        $this->assertTrue($rule->validate('01arz3ndektsv4rrffq69g5fav'));
        // 'I', 'L', 'O', 'U' are not in Crockford base32.
        $this->assertFalse($rule->validate('01ARZ3NDEKTSV4RRFFQ69G5FAI'));
        // The first character caps the 48-bit timestamp at 7.
        $this->assertFalse($rule->validate('81ARZ3NDEKTSV4RRFFQ69G5FAV'));
        $this->assertFalse($rule->validate('01ARZ3NDEKTSV4RRFFQ69G5FA'));
        $this->assertFalse($rule->validate(''));
        $this->assertEquals('This field must contain a valid ULID', $rule->getMessage());
    }

    public function testValidIban(): void
    {
        $rule = new ValidIban();

        $this->assertTrue($rule->validate('GB82 WEST 1234 5698 7654 32'));
        $this->assertTrue($rule->validate('DE89370400440532013000'));
        $this->assertTrue($rule->validate('gb82west12345698765432'));
        // A single changed digit breaks the mod-97 checksum.
        $this->assertFalse($rule->validate('GB82 WEST 1234 5698 7654 33'));
        $this->assertFalse($rule->validate('12345678'));
        $this->assertFalse($rule->validate(''));
        $this->assertEquals('This field must contain a valid IBAN', $rule->getMessage());
    }

    public function testValidIsbn(): void
    {
        $rule = new ValidIsbn();

        // ISBN-10, with and without hyphens, including an X check digit.
        $this->assertTrue($rule->validate('0-306-40615-2'));
        $this->assertTrue($rule->validate('097522980X'));
        // ISBN-13.
        $this->assertTrue($rule->validate('978-0-306-40615-7'));
        $this->assertTrue($rule->validate('9780306406157'));
        // A single changed digit breaks the checksum.
        $this->assertFalse($rule->validate('978-0-306-40615-8'));
        $this->assertFalse($rule->validate('0-306-40615-3'));
        $this->assertFalse($rule->validate('12345'));
        $this->assertFalse($rule->validate(''));
        $this->assertEquals('This field must contain a valid ISBN', $rule->getMessage());
    }

    public function testValidLuhn(): void
    {
        $rule = new ValidLuhn();

        $this->assertTrue($rule->validate('4111 1111 1111 1111'));
        $this->assertTrue($rule->validate('79927398713'));
        $this->assertTrue($rule->validate(79927398713));
        $this->assertFalse($rule->validate('79927398714'));
        $this->assertFalse($rule->validate('abc'));
        $this->assertFalse($rule->validate(''));
        $this->assertEquals('This field must contain a valid number', $rule->getMessage());
    }

    public function testValidMacAddress(): void
    {
        $rule = new ValidMacAddress();

        $this->assertTrue($rule->validate('00:1A:2B:3C:4D:5E'));
        $this->assertTrue($rule->validate('00-1a-2b-3c-4d-5e'));
        $this->assertFalse($rule->validate('00:1A:2B:3C:4D'));
        $this->assertFalse($rule->validate('not-a-mac'));
        $this->assertFalse($rule->validate(''));
        $this->assertEquals('This field must contain a valid MAC address', $rule->getMessage());
    }

    public function testValidPort(): void
    {
        $rule = new ValidPort();

        $this->assertTrue($rule->validate(443));
        $this->assertTrue($rule->validate('8080'));
        $this->assertTrue($rule->validate(1));
        $this->assertTrue($rule->validate(65535));
        $this->assertFalse($rule->validate(0));
        $this->assertFalse($rule->validate(65536));
        $this->assertFalse($rule->validate('-1'));
        $this->assertFalse($rule->validate('http'));
        $this->assertEquals('This field must contain a valid port number', $rule->getMessage());
    }

    public function testValidSemver(): void
    {
        $rule = new ValidSemver();

        $this->assertTrue($rule->validate('1.2.3'));
        $this->assertTrue($rule->validate('2.0.0-rc.1'));
        $this->assertTrue($rule->validate('1.0.0+build.5'));
        $this->assertTrue($rule->validate('1.0.0-alpha+001'));
        $this->assertFalse($rule->validate('1.2'));
        $this->assertFalse($rule->validate('v1.2.3'));
        $this->assertFalse($rule->validate('01.2.3'));
        $this->assertFalse($rule->validate(''));
        $this->assertEquals('This field must contain a valid semantic version', $rule->getMessage());
    }

    public function testValidFilename(): void
    {
        $rule = new ValidFilename();

        $this->assertTrue($rule->validate('report-2026.pdf'));
        $this->assertTrue($rule->validate('notes.txt'));
        // Traversal, separators, and specials are rejected.
        $this->assertFalse($rule->validate('../etc/passwd'));
        $this->assertFalse($rule->validate('dir/file.txt'));
        $this->assertFalse($rule->validate('dir\\file.txt'));
        $this->assertFalse($rule->validate('.'));
        $this->assertFalse($rule->validate('..'));
        $this->assertFalse($rule->validate("bad\x00name"));
        $this->assertFalse($rule->validate(''));
        $this->assertFalse($rule->validate(str_repeat('a', 256)));
        $this->assertEquals('This field must contain a valid filename', $rule->getMessage());
    }

    public function testIsArray(): void
    {
        $rule = new IsArray();

        $this->assertTrue($rule->validate(['a', 'b']));
        $this->assertTrue($rule->validate([]));
        $this->assertFalse($rule->validate('a,b'));
        $this->assertFalse($rule->validate(1));
        $this->assertEquals('This field must be an array', $rule->getMessage());
    }

    public function testMinCount(): void
    {
        $rule = new MinCount(2);

        $this->assertTrue($rule->validate(['a', 'b']));
        $this->assertTrue($rule->validate(['a', 'b', 'c']));
        $this->assertFalse($rule->validate(['a']));
        $this->assertFalse($rule->validate('ab'));
        $this->assertEquals(2, $rule->getCount());
        $this->assertEquals('This field must contain at least 2 items', $rule->getMessage());
    }

    public function testMaxCount(): void
    {
        $rule = new MaxCount(2);

        $this->assertTrue($rule->validate(['a', 'b']));
        $this->assertTrue($rule->validate([]));
        $this->assertFalse($rule->validate(['a', 'b', 'c']));
        $this->assertFalse($rule->validate('ab'));
        $this->assertEquals(2, $rule->getCount());
        $this->assertEquals('This field must contain at most 2 items', $rule->getMessage());
    }

    public function testInListEach(): void
    {
        $rule = new InListEach(['red', 'green', 'blue']);

        $this->assertTrue($rule->validate(['red', 'blue']));
        $this->assertTrue($rule->validate([]));
        $this->assertFalse($rule->validate(['red', 'purple']));
        $this->assertFalse($rule->validate(['red', ['nested']]));
        $this->assertFalse($rule->validate('red'));
        $this->assertEquals(['red', 'green', 'blue'], $rule->getValues());
        $this->assertEquals('This field may only contain allowed values', $rule->getMessage());
    }

    public function testMinAge(): void
    {
        $rule = new MinAge(18);

        $this->assertTrue($rule->validate(date('Y-m-d', strtotime('-30 years'))));
        $this->assertTrue($rule->validate(date('Y-m-d', strtotime('-18 years'))));
        $this->assertFalse($rule->validate(date('Y-m-d', strtotime('-17 years'))));
        $this->assertFalse($rule->validate(date('Y-m-d', strtotime('+1 day'))));
        $this->assertFalse($rule->validate('not a date'));
        $this->assertFalse($rule->validate(''));
        $this->assertEquals(18, $rule->getYears());
        $this->assertEquals('This field must be at least 18 years ago', $rule->getMessage());
    }

    public function testMaxAge(): void
    {
        $rule = new MaxAge(65);

        $this->assertTrue($rule->validate(date('Y-m-d', strtotime('-30 years'))));
        $this->assertTrue($rule->validate(date('Y-m-d', strtotime('-65 years'))));
        $this->assertFalse($rule->validate(date('Y-m-d', strtotime('-66 years'))));
        $this->assertFalse($rule->validate('not a date'));
        $this->assertFalse($rule->validate(''));
        $this->assertEquals(65, $rule->getYears());
        $this->assertEquals('This field must be no more than 65 years ago', $rule->getMessage());
    }
}
