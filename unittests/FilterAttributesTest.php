<?php

declare(strict_types=1);

use orange\dto\attributes\filters\Abs;
use orange\dto\attributes\filters\Ceil;
use orange\dto\attributes\filters\Clamp;
use orange\dto\attributes\filters\CollapseSpaces;
use orange\dto\attributes\filters\DefaultTo;
use orange\dto\attributes\filters\Floor;
use orange\dto\attributes\filters\HtmlDecode;
use orange\dto\attributes\filters\HtmlEncode;
use orange\dto\attributes\filters\NormalizeDateTime;
use orange\dto\attributes\filters\NormalizeLineEndings;
use orange\dto\attributes\filters\NormalizePhone;
use orange\dto\attributes\filters\NullIfEmpty;
use orange\dto\attributes\filters\OnlyAlpha;
use orange\dto\attributes\filters\OnlyAlphaNumeric;
use orange\dto\attributes\filters\OnlyDigits;
use orange\dto\attributes\filters\Pad;
use orange\dto\attributes\filters\StripControlChars;
use orange\dto\attributes\filters\StripSpaces;
use orange\dto\attributes\filters\Transliterate;
use orange\dto\attributes\filters\Round;
use orange\dto\attributes\filters\Slugify;
use orange\dto\attributes\filters\StripTags;
use orange\dto\attributes\filters\StrLimit;
use orange\dto\attributes\filters\ToBoolean;
use orange\dto\attributes\filters\ToFloat;
use orange\dto\attributes\filters\ToInteger;
use orange\dto\attributes\filters\ToLower;
use orange\dto\attributes\filters\ToString;
use orange\dto\attributes\filters\ToUpper;
use orange\dto\attributes\filters\Trim;
use orange\dto\attributes\filters\UcFirst;
use orange\dto\attributes\filters\UcWords;

final class FilterAttributesTest extends UnitTestHelper
{
    public function testToString(): void
    {
        $rule = new ToString();

        $this->assertSame('123', $rule->filter(123));
        $this->assertSame('1', $rule->filter(true));
        $this->assertSame('', $rule->filter(null));
        // Arrays are returned unchanged rather than triggering a conversion warning.
        $this->assertSame([1, 2], $rule->filter([1, 2]));
    }

    public function testToInteger(): void
    {
        $rule = new ToInteger();

        $this->assertSame(123, $rule->filter('123'));
        $this->assertSame(10, $rule->filter('10 apples'));
        $this->assertSame(0, $rule->filter(false));
        $this->assertSame(0, $rule->filter(null));
        // Arrays are returned unchanged rather than triggering a conversion warning.
        $this->assertSame([1, 2], $rule->filter([1, 2]));
    }

    public function testToBoolean(): void
    {
        $rule = new ToBoolean();

        $this->assertTrue($rule->filter(true));
        $this->assertTrue($rule->filter(1));
        $this->assertTrue($rule->filter(-1));
        $this->assertFalse($rule->filter(0));
        $this->assertTrue($rule->filter('true'));
        $this->assertTrue($rule->filter('TRUE'));
        $this->assertTrue($rule->filter('yes'));
        $this->assertTrue($rule->filter('YES'));
        $this->assertFalse($rule->filter('false'));
        $this->assertFalse($rule->filter('no'));
        $this->assertFalse($rule->filter(''));
        // Types with no truthy handling fall through to the default of false.
        $this->assertFalse($rule->filter(1.5));
        $this->assertFalse($rule->filter([1, 2, 3]));
    }

    public function testStrLimit(): void
    {
        $rule = new StrLimit(5);

        $this->assertSame('Hello', $rule->filter('Hello World'));
        $this->assertSame('Hey', $rule->filter('Hey'));
        // Non-string input is returned unchanged.
        $this->assertSame(12345, $rule->filter(12345));
        $this->assertSame([1, 2], $rule->filter([1, 2]));
    }

    public function testTrim(): void
    {
        $rule = new Trim();

        $this->assertSame('Orange', $rule->filter("  Orange \t\n"));
        $this->assertSame('a b', $rule->filter('a b'));
        // Non-string input is returned unchanged.
        $this->assertSame(42, $rule->filter(42));
    }

    public function testToFloat(): void
    {
        $rule = new ToFloat();

        $this->assertSame(1.5, $rule->filter('1.5'));
        $this->assertSame(10.0, $rule->filter('10'));
        $this->assertSame(0.0, $rule->filter('abc'));
        // Arrays are returned unchanged rather than triggering a conversion warning.
        $this->assertSame([1, 2], $rule->filter([1, 2]));
    }

    public function testToLower(): void
    {
        $rule = new ToLower();

        $this->assertSame('orange', $rule->filter('OrAnGe'));
        // Non-string input is returned unchanged.
        $this->assertSame(5, $rule->filter(5));
    }

    public function testToUpper(): void
    {
        $rule = new ToUpper();

        $this->assertSame('ORANGE', $rule->filter('OrAnGe'));
        // Non-string input is returned unchanged.
        $this->assertSame(5, $rule->filter(5));
    }

    public function testNullIfEmpty(): void
    {
        $rule = new NullIfEmpty();

        $this->assertNull($rule->filter(''));
        // Only an empty string is treated as empty; '0' and 0 survive.
        $this->assertSame('0', $rule->filter('0'));
        $this->assertSame(0, $rule->filter(0));
        $this->assertSame('value', $rule->filter('value'));
    }

    public function testDefaultTo(): void
    {
        $rule = new DefaultTo('fallback');

        $this->assertSame('fallback', $rule->filter(''));
        $this->assertSame('fallback', $rule->filter(null));
        $this->assertSame('given', $rule->filter('given'));
        $this->assertSame('0', $rule->filter('0'));
        $this->assertSame('fallback', $rule->getDefault());
    }

    public function testCollapseSpaces(): void
    {
        $rule = new CollapseSpaces();

        $this->assertSame('a b c', $rule->filter("  a   b \t c  "));
        // Non-string input is returned unchanged.
        $this->assertSame(7, $rule->filter(7));
    }

    public function testStripTags(): void
    {
        $rule = new StripTags();

        $this->assertSame('Hello world', $rule->filter('<b>Hello</b> world'));
        // Non-string input is returned unchanged.
        $this->assertSame([1], $rule->filter([1]));
    }

    public function testSlugify(): void
    {
        $rule = new Slugify();

        $this->assertSame('my-first-post', $rule->filter('My First Post'));
        $this->assertSame('hello-world', $rule->filter('  Hello, World!  '));
        $this->assertSame('a-b', $rule->filter('a---b'));
        // Non-string input is returned unchanged.
        $this->assertSame(5, $rule->filter(5));
    }

    public function testHtmlEncode(): void
    {
        $rule = new HtmlEncode();

        $this->assertSame('&lt;b&gt;Hi&lt;/b&gt;', $rule->filter('<b>Hi</b>'));
        $this->assertSame('Tom &amp; Jerry', $rule->filter('Tom & Jerry'));
        // Non-string input is returned unchanged.
        $this->assertSame(5, $rule->filter(5));
    }

    public function testRound(): void
    {
        $rule = new Round(2);

        $this->assertSame(1.23, $rule->filter(1.2345));
        $this->assertSame(3.0, $rule->filter('3'));
        $this->assertSame(2, $rule->getPrecision());
        // Non-numeric input is returned unchanged.
        $this->assertSame('abc', $rule->filter('abc'));

        $default = new Round();
        $this->assertSame(1.0, $default->filter(1.4));
        $this->assertSame(2.0, $default->filter(1.5));
    }

    public function testOnlyDigits(): void
    {
        $rule = new OnlyDigits();

        $this->assertSame('5551234567', $rule->filter('(555) 123-4567'));
        $this->assertSame('42', $rule->filter('item-42'));
        // Non-string input is returned unchanged.
        $this->assertSame(5, $rule->filter(5));
    }

    public function testUcWords(): void
    {
        $rule = new UcWords();

        $this->assertSame('Hello World', $rule->filter('hello world'));
        $this->assertSame('Orange Framework', $rule->filter('ORANGE framework'));
        // Non-string input is returned unchanged.
        $this->assertSame(5, $rule->filter(5));
    }

    public function testUcFirst(): void
    {
        $rule = new UcFirst();

        $this->assertSame('Hello world', $rule->filter('hello world'));
        $this->assertSame('Already', $rule->filter('Already'));
        $this->assertSame('', $rule->filter(''));
        // Non-string input is returned unchanged.
        $this->assertSame(5, $rule->filter(5));
    }

    public function testHtmlDecode(): void
    {
        $rule = new HtmlDecode();

        $this->assertSame('<b>bold & "quoted"</b>', $rule->filter('&lt;b&gt;bold &amp; &quot;quoted&quot;&lt;/b&gt;'));
        $this->assertSame("it's", $rule->filter('it&#039;s'));
        // Non-string input is returned unchanged.
        $this->assertSame(5, $rule->filter(5));
    }

    public function testOnlyAlpha(): void
    {
        $rule = new OnlyAlpha();

        $this->assertSame('abcDEF', $rule->filter('abc123DEF!@#'));
        $this->assertSame('', $rule->filter('123'));
        // Non-string input is returned unchanged.
        $this->assertSame(5, $rule->filter(5));
    }

    public function testOnlyAlphaNumeric(): void
    {
        $rule = new OnlyAlphaNumeric();

        $this->assertSame('abc123DEF', $rule->filter('abc-123 DEF!'));
        $this->assertSame('', $rule->filter('!@#'));
        // Non-string input is returned unchanged.
        $this->assertSame(5, $rule->filter(5));
    }

    public function testClamp(): void
    {
        $rule = new Clamp(1, 10);

        $this->assertSame(5, $rule->filter(5));
        $this->assertSame(1, $rule->filter(-3));
        $this->assertSame(10, $rule->filter(42));
        $this->assertSame(2.5, $rule->filter(2.5));
        $this->assertSame(10, $rule->filter('42'));
        // Non-numeric input is returned unchanged.
        $this->assertSame('abc', $rule->filter('abc'));
    }

    public function testCeil(): void
    {
        $rule = new Ceil();

        $this->assertSame(5.0, $rule->filter(4.2));
        $this->assertSame(5.0, $rule->filter('4.2'));
        $this->assertSame(-4.0, $rule->filter(-4.2));
        // Non-numeric input is returned unchanged.
        $this->assertSame('abc', $rule->filter('abc'));
    }

    public function testFloor(): void
    {
        $rule = new Floor();

        $this->assertSame(4.0, $rule->filter(4.8));
        $this->assertSame(4.0, $rule->filter('4.8'));
        $this->assertSame(-5.0, $rule->filter(-4.2));
        // Non-numeric input is returned unchanged.
        $this->assertSame('abc', $rule->filter('abc'));
    }

    public function testAbs(): void
    {
        $rule = new Abs();

        $this->assertSame(5, $rule->filter(-5));
        $this->assertSame(5, $rule->filter(5));
        $this->assertSame(2.5, $rule->filter(-2.5));
        $this->assertSame(7, $rule->filter('-7'));
        // Non-numeric input is returned unchanged.
        $this->assertSame('abc', $rule->filter('abc'));
    }

    public function testStripControlChars(): void
    {
        $rule = new StripControlChars();

        // Zero-width and control characters are removed.
        $this->assertSame('hello', $rule->filter("he\u{200B}llo\x00"));
        $this->assertSame('bom-free', $rule->filter("\u{FEFF}bom-free"));
        // Tabs and newlines survive.
        $this->assertSame("a\tb\nc", $rule->filter("a\tb\nc"));
        // Non-string input is returned unchanged.
        $this->assertSame(5, $rule->filter(5));
    }

    public function testNormalizeLineEndings(): void
    {
        $rule = new NormalizeLineEndings();

        $this->assertSame("a\nb\nc", $rule->filter("a\r\nb\rc"));
        $this->assertSame("already\nunix", $rule->filter("already\nunix"));
        // Non-string input is returned unchanged.
        $this->assertSame(5, $rule->filter(5));
    }

    public function testStripSpaces(): void
    {
        $rule = new StripSpaces();

        $this->assertSame('4111111111111111', $rule->filter('4111 1111 1111 1111'));
        $this->assertSame('abc', $rule->filter(" a\tb\nc "));
        // Non-string input is returned unchanged.
        $this->assertSame(5, $rule->filter(5));
    }

    public function testTransliterate(): void
    {
        $rule = new Transliterate();

        $this->assertSame('resume', $rule->filter('résumé'));
        $this->assertSame('Uber', $rule->filter('Über'));
        $this->assertSame('plain', $rule->filter('plain'));
        // Non-string input is returned unchanged.
        $this->assertSame(5, $rule->filter(5));
    }

    public function testPad(): void
    {
        $rule = new Pad(5);

        $this->assertSame('00042', $rule->filter('42'));
        $this->assertSame('00042', $rule->filter(42));
        // Values at or beyond the length are unchanged.
        $this->assertSame('123456', $rule->filter('123456'));
        // Non-string, non-integer input is returned unchanged.
        $this->assertSame(4.2, $rule->filter(4.2));

        $spaces = new Pad(4, ' ');
        $this->assertSame('  ab', $spaces->filter('ab'));
    }

    public function testNormalizeDateTime(): void
    {
        $rule = new NormalizeDateTime();

        $this->assertSame('2026-08-01 09:30:00', $rule->filter('Aug 1 2026 9:30am'));
        $this->assertSame('2026-08-01 00:00:00', $rule->filter('2026-08-01'));

        $dateOnly = new NormalizeDateTime('Y-m-d');
        $this->assertSame('2026-08-01', $dateOnly->filter('08/01/2026'));

        // Unparseable or non-string input is returned unchanged.
        $this->assertSame('not a date', $rule->filter('not a date'));
        $this->assertSame('', $rule->filter(''));
        $this->assertSame(5, $rule->filter(5));
    }

    public function testNormalizePhone(): void
    {
        $rule = new NormalizePhone();

        $this->assertSame('5551234567', $rule->filter('(555) 123-4567'));
        $this->assertSame('+15551234567', $rule->filter('+1 555.123.4567'));
        $this->assertSame('5551234', $rule->filter('555-1234'));
        // Non-string input is returned unchanged.
        $this->assertSame(5551234, $rule->filter(5551234));
    }
}
