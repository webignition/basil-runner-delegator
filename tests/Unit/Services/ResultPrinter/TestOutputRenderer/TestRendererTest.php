<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter\TestOutputRenderer;

use webignition\BasilRunner\Model\ResultPrinter\TestName;
use webignition\BasilRunner\Model\TestOutput\Test;
use webignition\BasilRunner\Services\ResultPrinter\Renderer\TestRenderer;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class TestRendererTest extends AbstractBaseTest
{
    private TestRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->renderer = new TestRenderer();
    }

    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(Test $test, TestName $expectedTestName)
    {
        $this->assertEquals($expectedTestName, $this->renderer->render($test));
    }

    public function renderDataProvider(): array
    {
        return [
            'default' => [
                'test' => $this->createTest('/relative/path.yml'),
                'expectedTestName' => new TestName('/relative/path.yml'),
            ],
        ];
    }

    private function createTest(
        string $relativePath
    ): Test {
        $test = \Mockery::mock(Test::class);
        $test
            ->shouldReceive('getRelativePath')
            ->andReturn($relativePath);

        return $test;
    }
}
