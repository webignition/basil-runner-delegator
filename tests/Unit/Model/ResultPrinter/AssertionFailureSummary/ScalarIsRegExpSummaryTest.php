<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ScalarIsRegExpSummary;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class ScalarIsRegExpSummaryTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(ScalarIsRegExpSummary $scalarIsRegExpSummary, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $scalarIsRegExpSummary->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'default' => [
                'scalarIsRegExpSummary' => new ScalarIsRegExpSummary('/invalid/'),
                'expectedRenderedString' => '* <comment>/invalid/</comment> is not a valid regular expression',
            ],
        ];
    }
}
