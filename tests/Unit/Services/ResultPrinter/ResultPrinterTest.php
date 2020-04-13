<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter;

use PHPUnit\Runner\BaseTestRunner;
use webignition\BaseBasilTestCase\BasilTestCaseInterface;
use webignition\BasilModels\StatementInterface;
use webignition\BasilParser\ActionParser;
use webignition\BasilParser\AssertionParser;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Services\ResultPrinter\ResultPrinter;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class ResultPrinterTest extends AbstractBaseTest
{
    /**
     * @dataProvider printerOutputDataProvider
     *
     * @param string[] $testPaths
     * @param string[] $stepNames
     * @param int[] $endStatuses
     * @param array<int, StatementInterface[]> $handledStatements
     * @param string $expectedOutput
     */
    public function testPrinterOutput(
        array $testPaths,
        array $stepNames,
        array $endStatuses,
        array $handledStatements,
        string $expectedOutput
    ) {
        $tests = $this->createBasilTestCases(
            $testPaths,
            $stepNames,
            $endStatuses,
            $handledStatements
        );

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

        $actionParser = ActionParser::create();
        $assertionParser = AssertionParser::create();

        return [
            'single test' => [
                'testPaths' => [
                    $root . '/test.yml',
                ],
                'stepNames' => [
                    'step one',
                ],
                'endStatuses' => [
                    BaseTestRunner::STATUS_PASSED,
                ],
                'handledStatements' => [
                    [
                        $assertionParser->parse('$page.url is "http://example.com/"'),
                    ],
                ],
                'expectedOutput' =>
                    "\033[1m" . 'test.yml' . "\033[0m" . "\n" .
                    '  ' . "\033[32m" . '✓' . "\033[0m" . ' ' . "\033[32m" . 'step one' . "\033[0m" . "\n" .
                    '    ' . "\033[32m" . '✓' . "\033[0m" . ' $page.url is "http://example.com/"' . "\n"
                ,
            ],
            'multiple tests' => [
                'testPaths' => [
                    $root . '/test1.yml',
                    $root . '/test2.yml',
                    $root . '/test2.yml',
                    $root . '/test3.yml',
                ],
                'stepNames' => [
                    'test one step one',
                    'test two step one',
                    'test two step two',
                    'test three step one',
                ],
                'endStatuses' => [
                    BaseTestRunner::STATUS_PASSED,
                    BaseTestRunner::STATUS_PASSED,
                    BaseTestRunner::STATUS_PASSED,
                    BaseTestRunner::STATUS_FAILURE,
                ],
                'handledStatements' => [
                    [
                        $assertionParser->parse('$page.url is "http://example.com/"'),
                        $assertionParser->parse('$page.title is "Hello, World!"'),
                    ],
                    [
                        $actionParser->parse('click $".successful"'),
                        $assertionParser->parse('$page.url is "http://example.com/successful/"')
                    ],
                    [
                        $actionParser->parse('click $".back"'),
                        $assertionParser->parse('$page.url is "http://example.com/"'),
                    ],
                    [
                        $actionParser->parse('click $".new"'),
                        $assertionParser->parse('$page.url is "http://example.com/new/"'),
                    ],
                ],
                'expectedOutput' =>
                    "\033[1m" . 'test1.yml' . "\033[0m" . "\n" .
                    '  ' . "\033[32m" . '✓' . "\033[0m" . ' ' . "\033[32m" . 'test one step one' . "\033[0m" . "\n" .
                    '    ' . "\033[32m" . '✓' . "\033[0m" . ' $page.url is "http://example.com/"' . "\n" .
                    '    ' . "\033[32m" . '✓' . "\033[0m" . ' $page.title is "Hello, World!"' . "\n" .
                    "\n" .
                    "\033[1m" . 'test2.yml' . "\033[0m" . "\n" .
                    '  ' . "\033[32m" . '✓' . "\033[0m" . ' ' . "\033[32m" . 'test two step one' . "\033[0m" . "\n" .
                    '    ' . "\033[32m" . '✓' . "\033[0m" . ' click $".successful"' . "\n" .
                    '    ' . "\033[32m" . '✓' . "\033[0m" . ' $page.url is "http://example.com/successful/"' . "\n" .
                    '  ' . "\033[32m" . '✓' . "\033[0m" . ' ' . "\033[32m" . 'test two step two' . "\033[0m" . "\n" .
                    '    ' . "\033[32m" . '✓' . "\033[0m" . ' click $".back"' . "\n" .
                    '    ' . "\033[32m" . '✓' . "\033[0m" . ' $page.url is "http://example.com/"' . "\n" .
                    "\n" .
                    "\033[1m" . 'test3.yml' . "\033[0m" . "\n" .
                    '  ' . "\033[31m" . 'x' . "\033[0m" . ' ' . "\033[31m" . 'test three step one' . "\033[0m" . "\n" .
                    '    ' . "\033[32m" . '✓' . "\033[0m" . ' click $".new"' . "\n" .
                    '    ' . "\033[31m" . 'x' . "\033[0m" . ' ' . "\033[37m" . "\033[41m" .
                    '$page.url is "http://example.com/new/"' . "\033[0m" . "\n"
                ,
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
     * @param int[] $endStatuses
     * @param array<int, StatementInterface[]> $handledStatements
     *
     * @return BasilTestCaseInterface[]
     */
    private function createBasilTestCases(
        array $testPaths,
        array $stepNames,
        array $endStatuses,
        array $handledStatements
    ): array {
        $testCases = [];

        foreach ($testPaths as $testIndex => $testPath) {
            $basilTestCase = \Mockery::mock(BasilTestCaseInterface::class);
            $basilTestCase
                ->shouldReceive('getBasilTestPath')
                ->andReturnValues($testPaths);

            $basilTestCase
                ->shouldReceive('getBasilStepName')
                ->andReturn($stepNames[$testIndex]);

            $basilTestCase
                ->shouldReceive('getStatus')
                ->andReturn($endStatuses[$testIndex]);

            $basilTestCase
                ->shouldReceive('getHandledStatements')
                ->andReturn($handledStatements[$testIndex]);

            $testCases[] = $basilTestCase;
        }

        return $testCases;
    }
}
