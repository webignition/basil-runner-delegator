<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter\TestOutputRenderer;

use webignition\BasilModels\Assertion\DerivedValueOperationAssertion;
use webignition\BasilParser\ActionParser;
use webignition\BasilParser\AssertionParser;
use webignition\BasilRunner\Model\TestOutput\StatementLine;
use webignition\BasilRunner\Services\ResultPrinter\Renderer\StatementLineRenderer;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class StatementLineRendererTest extends AbstractBaseTest
{
    private StatementLineRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->renderer = new StatementLineRenderer();
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

        return [
            'passed exists assertion' => [
                'statementLine' => StatementLine::createPassedStatementLine($existsAssertion),
                'expectedRenderedStatementLine' => '    <icon-success /> $".selector" exists',
            ],
            'failed exists assertion' => [
                'statementLine' => StatementLine::createFailedStatementLine($existsAssertion),
                'expectedRenderedStatementLine' =>
                    '    <icon-failure /> <highlighted-failure>$".selector" exists</highlighted-failure>'
                ,
            ],
            'failed derived exists assertion' => [
                'statementLine' => StatementLine::createFailedStatementLine(
                    new DerivedValueOperationAssertion($clickAction, '$".selector"', 'exists')
                ),
                'expectedRenderedStatementLine' =>
                    '    <icon-failure /> <highlighted-failure>$".selector" exists</highlighted-failure>' . "\n" .
                    '      <comment>> derived from:</comment> click $".selector"'
                ,
            ],
        ];
    }
}
