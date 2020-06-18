<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter;

use webignition\BasilRunner\Model\ResultPrinter\Success;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class SuccessTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(Success $success, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $success->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'default' => [
                'success' => new Success('content'),
                'expectedRenderedString' => '<success>content</success>',
            ],
        ];
    }
}
