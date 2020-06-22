<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter;

use webignition\BasilRunner\Model\ResultPrinter\Failure;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class FailureTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(Failure $failure, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $failure->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'default' => [
                'failure' => new Failure('content'),
                'expectedRenderedString' => '<failure>content</failure>',
            ],
        ];
    }
}
