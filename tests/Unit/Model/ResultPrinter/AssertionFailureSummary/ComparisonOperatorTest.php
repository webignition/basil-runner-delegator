<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ComparisonOperator;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class ComparisonOperatorTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(ComparisonOperator $comparisonOperator, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $comparisonOperator->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'is' => [
                'comparisonOperator' => new ComparisonOperator('is'),
                'expectedRenderedString' => 'is not equal to',
            ],
            'is-not' => [
                'comparisonOperator' => new ComparisonOperator('is-not'),
                'expectedRenderedString' => 'is equal to',
            ],
            'includes' => [
                'comparisonOperator' => new ComparisonOperator('includes'),
                'expectedRenderedString' => 'does not include',
            ],
            'excludes' => [
                'comparisonOperator' => new ComparisonOperator('excludes'),
                'expectedRenderedString' => 'does not exclude',
            ],
            'matches' => [
                'comparisonOperator' => new ComparisonOperator('matches'),
                'expectedRenderedString' => 'does not match regular expression',
            ],
        ];
    }
}
