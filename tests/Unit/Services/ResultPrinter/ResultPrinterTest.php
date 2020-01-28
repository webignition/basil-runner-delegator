<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter;

use webignition\BaseBasilTestCase\BasilTestCaseInterface;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Services\ResultPrinter\Formatter;
use webignition\BasilRunner\Services\ResultPrinter\ResultPrinter;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class ResultPrinterTest extends AbstractBaseTest
{
    /**
     * @dataProvider startTestOutputsBasilTestPathInBoldDataProvider
     *
     * @param string[] $testPaths
     * @param string $expectedOutput
     */
    public function testStartTestOutputsBasilTestPathInBold(array $testPaths, string $expectedOutput)
    {
        $tests = $this->createBasilTestCases($testPaths);

        $outResource = fopen('php://memory', 'w+');

        if (is_resource($outResource)) {
            $printer = new ResultPrinter($outResource);

            $this->exercisePrinter($printer, $tests);

            rewind($outResource);
            $outContent = stream_get_contents($outResource);
            fclose($outResource);

            $this->assertSame($expectedOutput, $outContent);
        } else {
            $this->fail('Failed to open resource "php://memory" for reading and writing');
        }
    }

    public function startTestOutputsBasilTestPathInBoldDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();
        $formatter = new Formatter();

        return [
            'single test' => [
                'testPaths' => [
                    $root . '/test.yml',
                ],
                'expectedOutput' => $formatter->makeBold('test.yml') . "\n",
            ],
            'multiple tests' => [
                'testPaths' => [
                    $root . '/test1.yml',
                    $root . '/test2.yml',
                    $root . '/test3.yml',
                ],
                'expectedOutput' =>
                    $formatter->makeBold('test1.yml') . "\n" .
                    $formatter->makeBold('test2.yml') . "\n" .
                    $formatter->makeBold('test3.yml') . "\n",
            ],
        ];
    }

    /**
     * @param ResultPrinter $printer
     * @param BasilTestCaseInterface[] $tests
     */
    private function exercisePrinter(ResultPrinter $printer, array $tests): void
    {
        foreach ($tests as $test) {
            $printer->startTest($test);
            $printer->endTest($test, 0.1);
        }
    }

    /**
     * @param string[] $testPaths
     *
     * @return BasilTestCaseInterface[]
     */
    private function createBasilTestCases(array $testPaths): array
    {
        $testCases = [];

        foreach ($testPaths as $testPath) {
            $basilTestCase = \Mockery::mock(BasilTestCaseInterface::class);
            $basilTestCase
                ->shouldReceive('getBasilTestPath')
                ->andReturnValues($testPaths);

            $testCases[] = $basilTestCase;
        }

        return $testCases;
    }
}
