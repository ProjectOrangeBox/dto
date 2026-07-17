<?php

declare(strict_types=1);

use orange\request\attributes\filters\CollapseSpaces;
use orange\request\attributes\filters\DefaultTo;
use orange\request\attributes\filters\NullIfEmpty;
use orange\request\attributes\filters\StripTags;
use orange\request\attributes\filters\StrLimit;
use orange\request\attributes\filters\ToBoolean;
use orange\request\attributes\filters\ToFloat;
use orange\request\attributes\filters\ToInteger;
use orange\request\attributes\filters\ToLower;
use orange\request\attributes\filters\ToString;
use orange\request\attributes\filters\ToUpper;
use orange\request\attributes\filters\Trim;

final class FilterAttributesTest extends UnitTestHelper
{
    public function testToString(): void
    {
        $rule = new ToString();

        $this->assertSame('123', $rule->filter(123));
        $this->assertSame('1', $rule->filter(true));
        $this->assertSame('', $rule->filter(null));
    }

    public function testToInteger(): void
    {
        $rule = new ToInteger();

        $this->assertSame(123, $rule->filter('123'));
        $this->assertSame(10, $rule->filter('10 apples'));
        $this->assertSame(0, $rule->filter(false));
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
}
