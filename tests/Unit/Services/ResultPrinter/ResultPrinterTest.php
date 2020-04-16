<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter;

use PHPUnit\Runner\BaseTestRunner;
use webignition\BaseBasilTestCase\BasilTestCaseInterface;
use webignition\BasilModels\StatementInterface;
use webignition\BasilParser\ActionParser;
use webignition\BasilParser\AssertionParser;
use webignition\BasilRunner\Services\ProjectRootPathProvider;
use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;
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

        $cof = new ConsoleOutputFactory();

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
                $cof->createTestPath('test.yml') . "\n" .
                    '  ' . $cof->createSuccess('✓') . ' ' . $cof->createSuccess('step one') . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' $page.url is "http://example.com/"' . "\n"
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
                    $cof->createTestPath('test1.yml') . "\n" .
                    '  ' . $cof->createSuccess('✓') . ' ' . $cof->createSuccess('test one step one') . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' $page.url is "http://example.com/"' . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' $page.title is "Hello, World!"' . "\n" .
                    "\n" .
                    $cof->createTestPath('test2.yml') . "\n" .
                    '  ' . $cof->createSuccess('✓') . ' ' . $cof->createSuccess('test two step one') . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' click $".successful"' . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' $page.url is "http://example.com/successful/"' . "\n" .
                    '  ' . $cof->createSuccess('✓') . ' ' . $cof->createSuccess('test two step two') . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' click $".back"' . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' $page.url is "http://example.com/"' . "\n" .
                    "\n" .
                    $cof->createTestPath('test3.yml') . "\n" .
                    '  ' . $cof->createFailure('x') . ' ' . $cof->createFailure('test three step one') . "\n" .
                    '    ' . $cof->createSuccess('✓') . ' click $".new"' . "\n" .
                    '    ' . $cof->createFailure('x') . ' ' . $cof->createHighlightedFailure(
                        '$page.url is "http://example.com/new/"'
                    ) . "\n"
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
