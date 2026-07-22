<?php

declare(strict_types=1);

use orange\dto\Dto;
use orange\dto\DtoAttribute;

final class DtoAttributeTest extends UnitTestHelper
{
    /**
     * A minimal attribute exposing a template so the base getMessage() logic can be tested.
     */
    private function makeAttribute(string $message = ''): DtoAttribute
    {
        return new class($message) extends DtoAttribute {
            protected string $errorMsg = '%s failed the check';
        };
    }

    public function testDefaultHumanNameIsUsed(): void
    {
        $rule = $this->makeAttribute();

        $this->assertEquals('This field failed the check', $rule->getMessage());
    }

    public function testProvidedHumanNameOverridesDefault(): void
    {
        $rule = $this->makeAttribute();

        $this->assertEquals('Name failed the check', $rule->getMessage('Name'));
    }

    public function testCustomMessageOverridesErrorTemplate(): void
    {
        $rule = $this->makeAttribute('%s is not acceptable');

        $this->assertEquals('Name is not acceptable', $rule->getMessage('Name'));
    }

    public function testGetMessageValuesAreAppendedToTemplate(): void
    {
        $rule = new class('') extends DtoAttribute {
            protected string $errorMsg = '%s must be at least %s characters';

            protected function getMessageValues(): array
            {
                return [5];
            }
        };

        $this->assertEquals('Name must be at least 5 characters', $rule->getMessage('Name'));
    }

    public function testRequestCanBeShared(): void
    {
        $request = new class(['field' => 'value']) extends Dto {};

        $rule = new class('') extends DtoAttribute {
            protected string $errorMsg = '%s';

            public function seenValue(string $key): mixed
            {
                return $this->dto->input($key);
            }
        };

        $rule->request($request);

        $this->assertEquals('value', $rule->seenValue('field'));
    }
}
