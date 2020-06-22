<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\WithValueComparedToValue;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class WithValueComparedToValueTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(WithValueComparedToValue $withValueComparedToValue, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $withValueComparedToValue->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'is' => [
                'withValueComparedToValue' => new WithValueComparedToValue(
                    'actual',
                    'expected',
                    'is'
                ),
                'expectedRenderedString' =>
                    'with value <comment>actual</comment> is not equal to <comment>expected</comment>',
            ],
            'is-not' => [
                'withValueComparedToValue' => new WithValueComparedToValue(
                    'actual',
                    'expected',
                    'is-not'
                ),
                'expectedRenderedString' =>
                    'with value <comment>actual</comment> is equal to <comment>expected</comment>',
            ],
            'includes' => [
                'withValueComparedToValue' => new WithValueComparedToValue(
                    'actual',
                    'expected',
                    'includes'
                ),
                'expectedRenderedString' =>
                    'with value <comment>actual</comment> does not include <comment>expected</comment>',
            ],
            'excludes' => [
                'withValueComparedToValue' => new WithValueComparedToValue(
                    'actual',
                    'actual',
                    'excludes'
                ),
                'expectedRenderedString' =>
                    'with value <comment>actual</comment> does not exclude <comment>actual</comment>',
            ],
            'matches' => [
                'withValueComparedToValue' => new WithValueComparedToValue(
                    'actual',
                    '/expected/',
                    'matches'
                ),
                'expectedRenderedString' =>
                    'with value <comment>actual</comment> does not match regular expression ' .
                    '<comment>/expected/</comment>',
            ],
        ];
    }
}
