<?php

declare(strict_types=1);

use orange\dto\Dto;
use orange\dto\attributes\Column;
use orange\dto\attributes\Label;
use orange\dto\attributes\Table;
use orange\dto\attributes\filters\ToInteger;
use orange\dto\attributes\filters\ToString;
use orange\dto\attributes\validations\GreaterThan;
use orange\dto\attributes\validations\IsArray;
use orange\dto\attributes\validations\IsRequired;
use orange\dto\attributes\validations\MaxCount;
use orange\dto\attributes\validations\MinCount;

/**
 * Child DTO used as the element type of a dto-array property.
 */
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

/**
 * Parent DTO with a required dto-array property plus count bounds.
 */
class OrderRequest extends Dto
{
    #[IsRequired]
    #[ToString]
    #[Column('customer')]
    #[Table('orders')]
    #[Label('Customer')]
    public protected(set) string $customer;

    #[IsRequired]
    #[IsArray(LineItem::class)]
    #[MinCount(1)]
    #[MaxCount(3)]
    #[Label('Lines')]
    public protected(set) array $lines;
}

/**
 * Parent DTO whose dto-array property is optional.
 */
class OptionalLinesRequest extends Dto
{
    #[IsRequired]
    public protected(set) string $name;

    #[IsArray(LineItem::class)]
    #[Label('Lines')]
    public protected(set) array $lines;
}

/**
 * Two levels of nesting — the children are themselves dto-array parents.
 */
class BundleRequest extends Dto
{
    #[IsRequired]
    #[IsArray(OrderRequest::class)]
    #[Label('Orders')]
    public protected(set) array $orders;
}

final class DtoArrayTest extends UnitTestHelper
{
    private function validInput(): array
    {
        return [
            'customer' => 'Johnny Appleseed',
            'lines' => [
                ['sku' => 'A1', 'qty' => '2'],
                ['sku' => 'B2', 'qty' => 1],
            ],
        ];
    }

    public function testValidDtoArrayBuildsChildren(): void
    {
        $request = new OrderRequest($this->validInput());

        $this->assertTrue($request->isValid());
        $this->assertSame([], $request->errors());

        $this->assertCount(2, $request->lines);
        $this->assertContainsOnlyInstancesOf(LineItem::class, $request->lines);

        // children carry validated (and filtered) values
        $this->assertSame('A1', $request->lines[0]->sku);
        $this->assertSame(2, $request->lines[0]->qty);
        $this->assertTrue($request->lines[0]->isValid());
        $this->assertTrue($request->lines[1]->isValid());
    }

    public function testValidOutputIsNestedPlainArrays(): void
    {
        $request = new OrderRequest($this->validInput());

        $expected = [
            'customer' => 'Johnny Appleseed',
            'lines' => [
                ['sku' => 'A1', 'qty' => 2],
                ['sku' => 'B2', 'qty' => 1],
            ],
        ];

        $this->assertSame($expected, $request->asArray());
        $this->assertSame(json_encode($expected), json_encode($request));
        $this->assertSame(['lines' => $expected['lines']], $request->only('lines'));
        $this->assertSame(['customer' => 'Johnny Appleseed'], $request->except('lines'));
    }

    public function testDbShapesSkipDtoArrays(): void
    {
        $request = new OrderRequest($this->validInput());

        // the parent shapes never contain the nested structure
        $this->assertSame(['customer' => 'Johnny Appleseed'], $request->asColumns());
        $this->assertSame(['orders' => ['customer' => 'Johnny Appleseed']], $request->asTable());

        // each child is persisted individually through its own shapes
        $this->assertSame(['sku' => 'A1', 'qty' => 2], $request->lines[0]->asColumns());
        $this->assertSame(['order_lines' => ['sku' => 'B2', 'qty' => 1]], $request->lines[1]->asTable());
    }

    public function testInvalidChildIsSingleParentError(): void
    {
        $input = $this->validInput();
        $input['lines'][1] = ['sku' => '', 'qty' => '0'];

        $request = new OrderRequest($input);

        $this->assertFalse($request->isValid());
        $this->assertSame(['lines' => ['Lines has 1 or more errors']], $request->errors());
        $this->assertSame(['customer'], $request->validKeys());
        $this->assertSame(['lines'], $request->invalidKeys());

        // the property is still assigned so the children can be extracted
        $this->assertCount(2, $request->lines);
        $this->assertTrue($request->lines[0]->isValid());
        $this->assertFalse($request->lines[1]->isValid());
        $this->assertSame(['Sku is required'], $request->lines[1]->errors()['sku']);
        $this->assertArrayHasKey('qty', $request->lines[1]->errors());

        // but an invalid field never reaches the outputs
        $this->assertSame(['customer' => 'Johnny Appleseed'], $request->asArray());
        $this->assertSame('{"customer":"Johnny Appleseed"}', json_encode($request));
    }

    public function testNonObjectEntryIsDistinctParentError(): void
    {
        $input = $this->validInput();
        $input['lines'][1] = 'garbage';

        $request = new OrderRequest($input);

        $this->assertFalse($request->isValid());
        $this->assertSame(['lines' => ['Lines contains a non-object entry']], $request->errors());

        // the bad slot still holds a child (built from []) so keys line up
        $this->assertInstanceOf(LineItem::class, $request->lines[1]);
        $this->assertFalse($request->lines[1]->isValid());
    }

    public function testNonArrayValueFailsWithoutAssignment(): void
    {
        $input = $this->validInput();
        $input['lines'] = 'not an array';

        $request = new OrderRequest($input);

        $this->assertFalse($request->isValid());
        // the count rules also report on a non-array — each rule is independent
        $this->assertContains('Lines must be an array', $request->errors()['lines']);

        // nothing to extract — the property stays uninitialized
        $this->assertFalse(new ReflectionProperty(OrderRequest::class, 'lines')->isInitialized($request));
    }

    public function testCountRulesStillApplyAndChildrenRemainExtractable(): void
    {
        $input = $this->validInput();
        $input['lines'] = array_fill(0, 4, ['sku' => 'A1', 'qty' => 1]);

        $request = new OrderRequest($input);

        $this->assertFalse($request->isValid());
        $this->assertSame(['lines' => ['Lines must contain at most 3 items']], $request->errors());

        // every child was still built and is individually valid
        $this->assertCount(4, $request->lines);
        $this->assertContainsOnlyInstancesOf(LineItem::class, $request->lines);
        $this->assertTrue($request->lines[3]->isValid());
    }

    public function testInputKeysArePreserved(): void
    {
        $input = [
            'customer' => 'Johnny Appleseed',
            'lines' => [
                7 => ['sku' => 'A1', 'qty' => 1],
                'extra' => ['sku' => 'B2', 'qty' => 2],
            ],
        ];

        $request = new OrderRequest($input);

        $this->assertTrue($request->isValid());
        $this->assertSame([7, 'extra'], array_keys($request->lines));
        $this->assertSame([7, 'extra'], array_keys($request->asArray()['lines']));
    }

    public function testAbsentOptionalDtoArrayIsEmptyArray(): void
    {
        $request = new OptionalLinesRequest(['name' => 'Johnny Appleseed']);

        $this->assertTrue($request->isValid());
        $this->assertSame([], $request->lines);
        $this->assertSame(['name' => 'Johnny Appleseed', 'lines' => []], $request->asArray());
    }

    public function testAbsentRequiredDtoArrayFailsButStaysExtractable(): void
    {
        $request = new OrderRequest(['customer' => 'Johnny Appleseed']);

        $this->assertFalse($request->isValid());
        $this->assertSame(['Lines is required'], $request->errors()['lines']);
        $this->assertSame([], $request->lines);
    }

    public function testNestedDtoArraysFlattenRecursively(): void
    {
        $request = new BundleRequest([
            'orders' => [
                [
                    'customer' => 'Johnny Appleseed',
                    'lines' => [['sku' => 'A1', 'qty' => '2']],
                ],
            ],
        ]);

        $this->assertTrue($request->isValid());
        $this->assertInstanceOf(OrderRequest::class, $request->orders[0]);
        $this->assertInstanceOf(LineItem::class, $request->orders[0]->lines[0]);

        $this->assertSame([
            'orders' => [
                [
                    'customer' => 'Johnny Appleseed',
                    'lines' => [['sku' => 'A1', 'qty' => 2]],
                ],
            ],
        ], $request->asArray());
    }

    public function testAllErrorsIncludesDotKeyedChildDetail(): void
    {
        $input = $this->validInput();
        $input['lines'][1] = ['sku' => '', 'qty' => '0'];

        $request = new OrderRequest($input);

        // errors() keeps the rollup; allErrors() adds the child detail
        $this->assertSame(['lines' => ['Lines has 1 or more errors']], $request->errors());

        $all = $request->allErrors();

        $this->assertSame(['Lines has 1 or more errors'], $all['lines']);
        $this->assertSame(['Sku is required'], $all['lines.1.sku']);
        $this->assertArrayHasKey('lines.1.qty', $all);

        // the valid child contributes nothing
        $this->assertArrayNotHasKey('lines.0.sku', $all);
    }

    public function testAllErrorsMatchesErrorsWhenNothingNested(): void
    {
        // fully valid — nothing at all
        $this->assertSame([], new OrderRequest($this->validInput())->allErrors());

        // a non-array value never assigns the property — no detail to add
        $input = $this->validInput();
        $input['lines'] = 'not an array';

        $request = new OrderRequest($input);

        $this->assertSame($request->errors(), $request->allErrors());

        // a plain DTO with no dto-arrays behaves identically
        $line = new LineItem(['sku' => '', 'qty' => '0']);

        $this->assertSame($line->errors(), $line->allErrors());
    }

    public function testAllErrorsRecursesThroughNestedDtoArrays(): void
    {
        $request = new BundleRequest([
            'orders' => [
                [
                    'customer' => 'Johnny Appleseed',
                    'lines' => [['sku' => '', 'qty' => '2']],
                ],
            ],
        ]);

        $this->assertFalse($request->isValid());
        $this->assertSame(['orders' => ['Orders has 1 or more errors']], $request->errors());

        $all = $request->allErrors();

        $this->assertSame(['Orders has 1 or more errors'], $all['orders']);
        $this->assertSame(['Lines has 1 or more errors'], $all['orders.0.lines']);
        $this->assertSame(['Sku is required'], $all['orders.0.lines.0.sku']);
    }

    public function testNonDtoChildClassThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new IsArray(stdClass::class);
    }
}
