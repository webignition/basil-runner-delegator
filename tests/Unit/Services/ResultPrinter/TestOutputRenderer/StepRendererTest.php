<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter\TestOutputRenderer;

use Facebook\WebDriver\Exception\InvalidSelectorException;
use PHPUnit\Runner\BaseTestRunner;
use webignition\BaseBasilTestCase\BasilTestCaseInterface;
use webignition\BasilDomIdentifierFactory\Factory;
use webignition\BasilModels\DataSet\DataSet;
use webignition\BasilModels\DataSet\DataSetInterface;
use webignition\BasilModels\StatementInterface;
use webignition\BasilParser\ActionParser;
use webignition\BasilParser\AssertionParser;
use webignition\BasilRunner\Model\TestOutput\Step;
use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;
use webignition\BasilRunner\Services\ResultPrinter\FailedAssertion\SummaryHandler;
use webignition\BasilRunner\Services\ResultPrinter\FailedAssertion\SummaryFactory;
use webignition\BasilRunner\Services\ResultPrinter\Renderer\ExceptionRenderer;
use webignition\BasilRunner\Services\ResultPrinter\Renderer\StatementLineRenderer;
use webignition\BasilRunner\Services\ResultPrinter\Renderer\StepRenderer;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;
use webignition\DomElementIdentifier\ElementIdentifier;
use webignition\SymfonyDomCrawlerNavigator\Exception\InvalidLocatorException;

class StepRendererTest extends AbstractBaseTest
{
    private StepRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $consoleOutputFactory = new ConsoleOutputFactory();

        $this->renderer = new StepRenderer(
            new ConsoleOutputFactory(),
            new StatementLineRenderer($consoleOutputFactory),
            new SummaryHandler(
                Factory::createFactory(),
                new SummaryFactory($consoleOutputFactory)
            ),
            new ExceptionRenderer(
                $consoleOutputFactory
            )
        );
    }

    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(Step $step, string $expectedRenderedStep)
    {
        $this->assertSame($expectedRenderedStep, $this->renderer->render($step));
    }

    public function renderDataProvider(): array
    {
        $cof = new ConsoleOutputFactory();
        $actionParser = ActionParser::create();
        $assertionParser = AssertionParser::create();

        $successPrefix = $cof->createSuccess('✓');
        $failurePrefix = $cof->createFailure('x');

        return [
            'passed, no statements' => [
                'step' => new Step($this->createTest(
                    BaseTestRunner::STATUS_PASSED,
                    'passed step name',
                    [],
                    '',
                    '',
                    null,
                    null
                )),
                'expectedRenderedStep' =>
                    '  <icon-success /> <success>passed step name</success>' . "\n"
                ,
            ],
            'failed, no statements' => [
                'step' => new Step($this->createTest(
                    BaseTestRunner::STATUS_FAILURE,
                    'failed step name',
                    [],
                    '',
                    '',
                    null,
                    null
                )),
                'expectedRenderedStep' =>
                    '  <icon-failure /> <failure>failed step name</failure>' . "\n"
                ,
            ],
            'unknown, no statements' => [
                'step' => new Step($this->createTest(
                    BaseTestRunner::STATUS_ERROR,
                    'unknown step name',
                    [],
                    '',
                    '',
                    null,
                    null
                )),
                'expectedRenderedStep' =>
                    '  <icon-unknown /> <failure>unknown step name</failure>' . "\n"
                ,
            ],
            'passed, click statement completed' => [
                'step' => new Step($this->createTest(
                    BaseTestRunner::STATUS_PASSED,
                    'passed step name',
                    [
                        $actionParser->parse('click $".selector"'),
                    ],
                    '',
                    '',
                    null,
                    null
                )),
                'expectedRenderedStep' =>
                    '  <icon-success /> <success>passed step name</success>' . "\n" .
                    '    ' . $successPrefix . ' click $".selector"'
                ,
            ],
            'failed, exists assertion failed' => [
                'step' => new Step($this->createTest(
                    BaseTestRunner::STATUS_FAILURE,
                    'failed step name',
                    [
                        $assertionParser->parse('$".selector" exists'),
                    ],
                    '',
                    '',
                    null,
                    null
                )),
                'expectedRenderedStep' =>
                    '  <icon-failure /> <failure>failed step name</failure>' . "\n" .
                    '    ' . $failurePrefix . ' ' . $cof->createHighlightedFailure('$".selector" exists') . "\n" .
                    '    * Element ' . $cof->createComment('$".selector"') . ' identified by:' . "\n" .
                    '        - CSS selector: ' . $cof->createComment('.selector') . "\n" .
                    '        - ordinal position: ' . $cof->createComment('1') . "\n" .
                    '      does not exist'
                ,
            ],
            'failed, is assertion failed' => [
                'step' => new Step($this->createTest(
                    BaseTestRunner::STATUS_FAILURE,
                    'failed step name',
                    [
                        $assertionParser->parse('$page.title is "Foo"'),
                    ],
                    'Foo',
                    'Bar',
                    null,
                    null
                )),
                'expectedRenderedStep' =>
                    '  <icon-failure /> <failure>failed step name</failure>' . "\n" .
                    '    ' . $failurePrefix . ' ' . $cof->createHighlightedFailure('$page.title is "Foo"') . "\n" .
                    '    * ' . $cof->createComment('Bar') . ' is not equal to ' . $cof->createComment('Foo')
                ,
            ],
            'failed, first assertion passed, second assertion failed' => [
                'step' => new Step($this->createTest(
                    BaseTestRunner::STATUS_FAILURE,
                    'failed step name',
                    [
                        $assertionParser->parse('$page.url is "http://example.com/'),
                        $assertionParser->parse('$page.title is "Foo"'),
                    ],
                    'Foo',
                    'Bar',
                    null,
                    null
                )),
                'expectedRenderedStep' =>
                    '  <icon-failure /> <failure>failed step name</failure>' . "\n" .
                    '    ' . $successPrefix . ' $page.url is "http://example.com/' . "\n" .
                    '    ' . $failurePrefix . ' ' . $cof->createHighlightedFailure('$page.title is "Foo"') . "\n" .
                    '    * ' . $cof->createComment('Bar') . ' is not equal to ' . $cof->createComment('Foo')
                ,
            ],
            'failed, elemental assertion uses invalid CSS selector' => [
                'step' => new Step($this->createTest(
                    BaseTestRunner::STATUS_FAILURE,
                    'failed step name',
                    [
                        $assertionParser->parse('$"a[href=https://example.com]" exists'),
                    ],
                    'Foo',
                    'Bar',
                    null,
                    new InvalidLocatorException(
                        new ElementIdentifier('a[href=https://example.com]'),
                        \Mockery::mock(InvalidSelectorException::class)
                    )
                )),
                'expectedRenderedStep' =>
                    '  <icon-failure /> <failure>failed step name</failure>' . "\n" .
                    '    ' . $failurePrefix . ' '
                    . $cof->createHighlightedFailure('$"a[href=https://example.com]" exists') . "\n" .
                    '    * Element '
                    . $cof->createComment('$"a[href=https://example.com]"') . ' identified by:' . "\n" .
                    '        - CSS selector: ' . $cof->createComment('a[href=https://example.com]') . "\n" .
                    '        - ordinal position: ' . $cof->createComment('1') . "\n" .
                    '      does not exist' . "\n" .
                    '    * CSS selector ' . $cof->createComment('a[href=https://example.com]') . ' is not valid'
                ,
            ],
            'passed, has data' => [
                'step' => new Step($this->createTest(
                    BaseTestRunner::STATUS_PASSED,
                    'passed step name',
                    [
                        $actionParser->parse('set $".search" to $data.search'),
                        $assertionParser->parse('$page.title matches $data.expected_title_pattern'),
                    ],
                    '',
                    '',
                    new DataSet(
                        'data set name',
                        [
                            'search' => 'value1',
                            'expected_title_pattern' => 'value2',
                        ]
                    ),
                    null
                )),
                'expectedRenderedStep' =>
                    '  <icon-success /> <success>passed step name: data set name</success>' . "\n" .
                    '      $search: <comment>value1</comment>' . "\n" .
                    '      $expected_title_pattern: <comment>value2</comment>' . "\n" .
                    "\n" .
                    '    ' . $successPrefix . ' set $".search" to $data.search' . "\n" .
                    '    ' . $successPrefix . ' $page.title matches $data.expected_title_pattern'
                ,
            ],
        ];
    }

    /**
     * @param int $status
     * @param string $basilStepName
     * @param StatementInterface[] $handledStatements
     * @param string $expectedValue
     * @param string $actualValue
     * @return BasilTestCaseInterface
     */
    private function createTest(
        int $status,
        string $basilStepName,
        array $handledStatements,
        string $expectedValue,
        string $actualValue,
        ?DataSetInterface $currentDataSet,
        ?\Throwable $lastException
    ): BasilTestCaseInterface {
        $test = \Mockery::mock(BasilTestCaseInterface::class);
        $test
            ->shouldReceive('getStatus')
            ->andReturn($status);

        $test
            ->shouldReceive('getBasilStepName')
            ->andReturn($basilStepName);

        $test
            ->shouldReceive('getHandledStatements')
            ->andReturn($handledStatements);

        $test
            ->shouldReceive('getExpectedValue')
            ->andReturn($expectedValue);

        $test
            ->shouldReceive('getExaminedValue')
            ->andReturn($actualValue);

        $test
            ->shouldReceive('getCurrentDataSet')
            ->andReturn($currentDataSet);

        $test
            ->shouldReceive('getLastException')
            ->andReturn($lastException);

        return $test;
    }
}
