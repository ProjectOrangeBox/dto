<?php

declare(strict_types=1);

use orange\request\Request;
use orange\request\attributes\validations\Alpha;
use orange\request\attributes\validations\AlphaDash;
use orange\request\attributes\validations\AlphaNumeric;
use orange\request\attributes\validations\AlphaNumericSpaces;
use orange\request\attributes\validations\Between;
use orange\request\attributes\validations\BetweenLength;
use orange\request\attributes\validations\Contains;
use orange\request\attributes\validations\DateFormat;
use orange\request\attributes\validations\Decimal;
use orange\request\attributes\validations\Differs;
use orange\request\attributes\validations\EndsWith;
use orange\request\attributes\validations\ExactLength;
use orange\request\attributes\validations\GreaterThan;
use orange\request\attributes\validations\GreaterThanEqualTo;
use orange\request\attributes\validations\InList;
use orange\request\attributes\validations\Integer as IntegerValidation;
use orange\request\attributes\validations\IsNatural;
use orange\request\attributes\validations\IsNaturalNoZero;
use orange\request\attributes\validations\IsRequired;
use orange\request\attributes\validations\LessThan;
use orange\request\attributes\validations\LessThanEqualTo;
use orange\request\attributes\validations\Matches;
use orange\request\attributes\validations\MaxLength;
use orange\request\attributes\validations\MinLength;
use orange\request\attributes\validations\NotInList;
use orange\request\attributes\validations\Numeric;
use orange\request\attributes\validations\RegexMatch;
use orange\request\attributes\validations\RequiredIf;
use orange\request\attributes\validations\RequiredWith;
use orange\request\attributes\validations\Slug;
use orange\request\attributes\validations\StartsWith;
use orange\request\attributes\validations\ValidBase64;
use orange\request\attributes\validations\ValidCreditCard;
use orange\request\attributes\validations\ValidDate;
use orange\request\attributes\validations\ValidEmail;
use orange\request\attributes\validations\ValidEmails;
use orange\request\attributes\validations\ValidHexColor;
use orange\request\attributes\validations\ValidHostname;
use orange\request\attributes\validations\ValidIp;
use orange\request\attributes\validations\ValidJson;
use orange\request\attributes\validations\ValidTimezone;
use orange\request\attributes\validations\ValidUrl;
use orange\request\attributes\validations\ValidUuid;

final class ValidationAttributesTest extends UnitTestHelper
{
    protected function makeRequest(array $input): Request
    {
        return new class($input) extends Request {
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
        $this->assertFalse($rule->validate(''));
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
        $this->assertFalse($rule->validate(''));
        $this->assertEquals('shipping', $rule->getField());
        $this->assertEquals('This field is required', $rule->getMessage());

        // The companion field is empty, so this field is optional.
        $optional = new RequiredWith('shipping');
        $optional->request($this->makeRequest(['shipping' => '']));
        $this->assertTrue($optional->validate(''));
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
            new Alpha(),
            new AlphaDash(),
            new AlphaNumeric(),
            new AlphaNumericSpaces(),
            new Between(1, 10),
            new BetweenLength(1, 10),
            new Contains('a'),
            new DateFormat('Y-m-d'),
            new Decimal(),
            new EndsWith('a'),
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
            new NotInList(['a', 'b']),
            new Numeric(),
            new RegexMatch('/^[a-z]+$/'),
            new Slug(),
            new StartsWith('a'),
            new ValidBase64(),
            new ValidCreditCard(),
            new ValidDate(),
            new ValidEmail(),
            new ValidEmails(),
            new ValidHexColor(),
            new ValidHostname(),
            new ValidIp(),
            new ValidJson(),
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
}
