<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\WithValue;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class WithValueTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(WithValue $withValue, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $withValue->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'indent depth 0' => [
                'withValue' => new WithValue('no indent'),
                'expectedRenderedString' => 'with value <comment>no indent</comment>',
            ],
            'indent depth 1' => [
                'withValue' => new WithValue('indent depth 1', 1),
                'expectedRenderedString' => '  with value <comment>indent depth 1</comment>',
            ],
        ];
    }
}
