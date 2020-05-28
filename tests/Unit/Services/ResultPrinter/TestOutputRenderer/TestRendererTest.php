<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter\TestOutputRenderer;

use webignition\BasilRunner\Model\TestOutput\Test;
use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;
use webignition\BasilRunner\Services\ResultPrinter\Renderer\TestRenderer;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class TestRendererTest extends AbstractBaseTest
{
    private TestRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->renderer = new TestRenderer(
            new ConsoleOutputFactory()
        );
    }

    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(Test $test, string $expectedRenderedTest)
    {
        $this->assertSame($expectedRenderedTest, $this->renderer->render($test));
    }

    public function renderDataProvider(): array
    {
        $consoleOutputFactory = new ConsoleOutputFactory();

        return [
            'default' => [
                'test' => $this->createTest('/relative/path.yml'),
                'expectedRenderedTest' =>
                    $consoleOutputFactory->createTestPath('/relative/path.yml')
                ,
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
