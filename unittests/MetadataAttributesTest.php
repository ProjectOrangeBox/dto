<?php

declare(strict_types=1);

use orange\dto\attributes\Column;
use orange\dto\attributes\FieldName;
use orange\dto\attributes\Label;
use orange\dto\attributes\Table;

final class MetadataAttributesTest extends UnitTestHelper
{
    public function testColumn(): void
    {
        $attribute = new Column('fav_color');

        $this->assertEquals('fav_color', $attribute->getName());
    }

    public function testColumnDefaultsToEmptyString(): void
    {
        $attribute = new Column();

        $this->assertEquals('', $attribute->getName());
    }

    public function testFieldName(): void
    {
        $attribute = new FieldName('clr');

        $this->assertEquals('clr', $attribute->getName());
    }

    public function testFieldNameDefaultsToEmptyString(): void
    {
        $attribute = new FieldName();

        $this->assertEquals('', $attribute->getName());
    }

    public function testLabel(): void
    {
        $attribute = new Label('Favorite Color');

        $this->assertEquals('Favorite Color', $attribute->getName());
    }

    public function testLabelDefaultsToEmptyString(): void
    {
        $attribute = new Label();

        $this->assertEquals('', $attribute->getName());
    }

    public function testTable(): void
    {
        $attribute = new Table('user', 'default');

        $this->assertEquals('user', $attribute->getName());
        $this->assertEquals('default', $attribute->getDatabase());
    }

    public function testTableDefaultsToEmptyStrings(): void
    {
        $attribute = new Table();

        $this->assertEquals('', $attribute->getName());
        $this->assertEquals('', $attribute->getDatabase());
    }
}
