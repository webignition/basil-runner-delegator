<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter;

use webignition\BasilRunner\Model\ResultPrinter\TestName;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class TestNameTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(TestName $testName, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $testName->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'default' => [
                'testName' => new TestName('/test.yml'),
                'expectedRenderedString' => '<test-name>/test.yml</test-name>',
            ],
        ];
    }
}
