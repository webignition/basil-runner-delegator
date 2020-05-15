<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter;

use webignition\BasilModels\Assertion\DerivedElementExistsAssertion;
use webignition\BasilParser\ActionParser;
use webignition\BasilParser\AssertionParser;
use webignition\BasilRunner\Model\TestOutput\StatementLine;
use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;
use webignition\BasilRunner\Services\ResultPrinter\StatementLineRenderer;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class StatementLineRendererTest extends AbstractBaseTest
{
    /**
     * @var StatementLineRenderer
     */
    private $renderer;

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
        $clickAction = $actionParser->parse('click $".selector');

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
                    new DerivedElementExistsAssertion($clickAction, '$".selector"')
                ),
                'expectedRenderedStatementLine' =>
                    $passedPrefix . ' ' . $existsAssertion->getSource() . "\n" .
                    '  ' . $consoleOutputFactory->createComment('> derived from:') . ' ' . $clickAction->getSource()
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
                    new DerivedElementExistsAssertion($clickAction, '$".selector"')
                ),
                'expectedRenderedStatementLine' =>
                    $failedPrefix . ' ' .
                    $consoleOutputFactory->createHighlightedFailure($existsAssertion->getSource()) . "\n" .
                    '  ' . $consoleOutputFactory->createComment('> derived from:') . ' ' . $clickAction->getSource()
                ,
            ],
        ];
    }
}
