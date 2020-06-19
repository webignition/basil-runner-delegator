<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\Property;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class PropertyTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(Property $property, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $property->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'no padding' => [
                'property' => new Property('key', 'value'),
                'expectedRenderedString' => '  - key: <comment>value</comment>',
            ],
            'padding single space' => [
                'property' => new Property('key', 'value', ' '),
                'expectedRenderedString' => '  - key:  <comment>value</comment>',
            ],
            'padding two spaces' => [
                'property' => new Property('key', 'value', '  '),
                'expectedRenderedString' => '  - key:   <comment>value</comment>',
            ],
        ];
    }
}
