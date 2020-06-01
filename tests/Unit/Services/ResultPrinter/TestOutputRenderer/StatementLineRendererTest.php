<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter\TestOutputRenderer;

use webignition\BasilModels\Action\Action;
use webignition\BasilModels\Action\ResolvedAction;
use webignition\BasilModels\Assertion\Assertion;
use webignition\BasilModels\Assertion\DerivedValueOperationAssertion;
use webignition\BasilModels\Assertion\ResolvedAssertion;
use webignition\BasilParser\ActionParser;
use webignition\BasilParser\AssertionParser;
use webignition\BasilRunner\Model\TestOutput\StatementLine;
use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;
use webignition\BasilRunner\Services\ResultPrinter\Renderer\StatementLineRenderer;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class StatementLineRendererTest extends AbstractBaseTest
{
    private StatementLineRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->renderer = new StatementLineRenderer(
            new ConsoleOutputFactory()
        );
    }

    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(StatementLine $statementLine, string $expectedRenderedStatementLine)
    {
        $this->assertSame($expectedRenderedStatementLine, $this->renderer->render($statementLine));
    }

    public function renderDataProvider(): array
    {
        $actionParser = ActionParser::create();
        $assertionParser = AssertionParser::create();

        $existsAssertion = $assertionParser->parse('$".selector" exists');
        $clickAction = $actionParser->parse('click $".selector"');
        $unresolvedIsAssertion = new Assertion(
            '$page_import_name.elements.selector is "value"',
            '$page_import_name.elements.selector',
            'is',
            '"value"'
        );
        $unresolvedClickAction = new Action(
            'click $page_import_name.elements.selector',
            'click',
            '$page_import_name.elements.selector',
            '$page_import_name.elements.selector'
        );
        $resolvedIsAssertion = new ResolvedAssertion($unresolvedIsAssertion, '$".selector"', '"value"');
        $resolvedClickAction = new ResolvedAction($unresolvedClickAction, '$".selector"');

        $consoleOutputFactory = new ConsoleOutputFactory();

        $passedPrefix = $consoleOutputFactory->createSuccess('✓');
        $failedPrefix = $consoleOutputFactory->createFailure('x');

        return [
            'passed exists assertion' => [
                'statementLine' => StatementLine::createPassedStatementLine($existsAssertion),
                'expectedRenderedStatementLine' => $passedPrefix . ' ' . $existsAssertion->getSource(),
            ],
            'passed click action assertion' => [
                'statementLine' => StatementLine::createPassedStatementLine($clickAction),
                'expectedRenderedStatementLine' => $passedPrefix . ' ' . $clickAction->getSource(),
            ],
            'passed derived exists assertion' => [
                'statementLine' => StatementLine::createPassedStatementLine(
                    new DerivedValueOperationAssertion($clickAction, '$".selector"', 'exists')
                ),
                'expectedRenderedStatementLine' =>
                    $passedPrefix . ' ' . $existsAssertion->getSource() . "\n" .
                    '  ' . $consoleOutputFactory->createComment('> derived from:') . ' ' . $clickAction->getSource()
                ,
            ],
            'passed resolved assertion' => [
                'statementLine' => StatementLine::createPassedStatementLine($resolvedIsAssertion),
                'expectedRenderedStatementLine' =>
                    $passedPrefix . ' $".selector" is "value"' . "\n" .
                    '  ' . $consoleOutputFactory->createComment('> resolved from:') .
                    ' ' . $unresolvedIsAssertion->getSource()
                ,
            ],
            'passed resolved action' => [
                'statementLine' => StatementLine::createPassedStatementLine($resolvedClickAction),
                'expectedRenderedStatementLine' =>
                    $passedPrefix . ' click $".selector"' . "\n" .
                    '  ' . $consoleOutputFactory->createComment('> resolved from:') .
                    ' ' . $unresolvedClickAction->getSource()
                ,
            ],
            'failed exists assertion' => [
                'statementLine' => StatementLine::createFailedStatementLine($existsAssertion),
                'expectedRenderedStatementLine' =>
                    $failedPrefix . ' ' .
                    $consoleOutputFactory->createHighlightedFailure($existsAssertion->getSource())
                ,
            ],
            'failed click action assertion' => [
                'statementLine' => StatementLine::createFailedStatementLine($clickAction),
                'expectedRenderedStatementLine' =>
                    $failedPrefix . ' ' .
                    $consoleOutputFactory->createHighlightedFailure($clickAction->getSource())
                ,
            ],
            'failed derived exists assertion' => [
                'statementLine' => StatementLine::createFailedStatementLine(
                    new DerivedValueOperationAssertion($clickAction, '$".selector"', 'exists')
                ),
                'expectedRenderedStatementLine' =>
                    $failedPrefix . ' ' .
                    $consoleOutputFactory->createHighlightedFailure($existsAssertion->getSource()) . "\n" .
                    '  ' . $consoleOutputFactory->createComment('> derived from:') . ' ' . $clickAction->getSource()
                ,
            ],
            'failed resolved assertion' => [
                'statementLine' => StatementLine::createFailedStatementLine($resolvedIsAssertion),
                'expectedRenderedStatementLine' =>
                    $failedPrefix . ' ' .
                    $consoleOutputFactory->createHighlightedFailure('$".selector" is "value"') . "\n" .
                    '  ' . $consoleOutputFactory->createComment('> resolved from:') .
                    ' ' . $unresolvedIsAssertion->getSource()
                ,
            ],
            'failed resolved action' => [
                'statementLine' => StatementLine::createFailedStatementLine($resolvedClickAction),
                'expectedRenderedStatementLine' =>
                    $failedPrefix . ' ' .
                    $consoleOutputFactory->createHighlightedFailure('click $".selector"') . "\n" .
                    '  ' . $consoleOutputFactory->createComment('> resolved from:') .
                    ' ' . $unresolvedClickAction->getSource()
                ,
            ],
            'failed derived resolved exists assertion' => [
                'statementLine' => StatementLine::createFailedStatementLine(
                    new DerivedValueOperationAssertion($resolvedClickAction, '$".selector"', 'exists')
                ),
                'expectedRenderedStatementLine' =>
                    $failedPrefix . ' ' .
                    $consoleOutputFactory->createHighlightedFailure($existsAssertion->getSource()) . "\n" .
                    '  ' . $consoleOutputFactory->createComment('> derived from:') .
                    ' ' . $clickAction->getSource() . "\n" .
                    '  ' . $consoleOutputFactory->createComment('> resolved from:') .
                    ' ' . $unresolvedClickAction->getSource()
                ,
            ],
        ];
    }
}
