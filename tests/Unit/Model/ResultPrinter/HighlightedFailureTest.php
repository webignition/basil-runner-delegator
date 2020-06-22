<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter;

use webignition\BasilRunner\Model\ResultPrinter\HighlightedFailure;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class HighlightedFailureTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(HighlightedFailure $failure, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $failure->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'default' => [
                'failure' => new HighlightedFailure('content'),
                'expectedRenderedString' => '<highlighted-failure>content</highlighted-failure>',
            ],
        ];
    }
}
