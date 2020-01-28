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
     * @dataProvider printerOutputDataProvider
     *
     * @param string[] $testPaths
     * @param string[] $stepNames
     * @param string $expectedOutput
     */
    public function testPrinterOutput(array $testPaths, array $stepNames, string $expectedOutput)
    {
        $tests = $this->createBasilTestCases($testPaths, $stepNames);

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

    public function printerOutputDataProvider(): array
    {
        $root = (new ProjectRootPathProvider())->get();
        $formatter = new Formatter();

        return [
            'single test' => [
                'testPaths' => [
                    $root . '/test.yml',
                ],
                'step names' => [
                    'step one',
                ],
                'expectedOutput' =>
                    $formatter->makeBold('test.yml') . "\n" .
                    '    step one' . "\n"
                ,
            ],
            'multiple tests' => [
                'testPaths' => [
                    $root . '/test1.yml',
                    $root . '/test2.yml',
                    $root . '/test2.yml',
                    $root . '/test3.yml',
                ],
                'step names' => [
                    'test one step one',
                    'test two step one',
                    'test two step two',
                    'test three step one',
                ],
                'expectedOutput' =>
                    $formatter->makeBold('test1.yml') . "\n" .
                    '    test one step one' . "\n" .
                    $formatter->makeBold('test2.yml') . "\n" .
                    '    test two step one' . "\n" .
                    '    test two step two' . "\n" .
                    $formatter->makeBold('test3.yml') . "\n" .
                    '    test three step one' . "\n"
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
     * @param string[] $stepNames
     *
     * @return BasilTestCaseInterface[]
     */
    private function createBasilTestCases(array $testPaths, array $stepNames): array
    {
        $testCases = [];

        foreach ($testPaths as $testIndex => $testPath) {
            $basilTestCase = \Mockery::mock(BasilTestCaseInterface::class);
            $basilTestCase
                ->shouldReceive('getBasilTestPath')
                ->andReturnValues($testPaths);

            $basilTestCase
                ->shouldReceive('getBasilStepName')
                ->andReturn($stepNames[$testIndex]);

            $testCases[] = $basilTestCase;
        }

        return $testCases;
    }
}
