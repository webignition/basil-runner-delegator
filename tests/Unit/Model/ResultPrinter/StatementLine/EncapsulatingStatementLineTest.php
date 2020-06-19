<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter\StatementLine;

use webignition\BasilModels\Action\Action;
use webignition\BasilModels\Action\ResolvedAction;
use webignition\BasilModels\Assertion\Assertion;
use webignition\BasilModels\Assertion\DerivedValueOperationAssertion;
use webignition\BasilModels\Assertion\ResolvedAssertion;
use webignition\BasilParser\ActionParser;
use webignition\BasilRunner\Model\ResultPrinter\StatementLine\EncapsulatingStatementLine;
use webignition\BasilRunner\Model\TestOutput\Status;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class EncapsulatingStatementLineTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(EncapsulatingStatementLine $statementLine, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $statementLine->render());
    }

    public function renderDataProvider(): array
    {
        $actionParser = ActionParser::create();

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

        return [
            'passed derived exists assertion' => [
                'statementLine' => new EncapsulatingStatementLine(
                    new DerivedValueOperationAssertion($clickAction, '$".selector"', 'exists'),
                    Status::SUCCESS
                ),
                'expectedRenderedStatementLine' =>
                    '<icon-success /> $".selector" exists' . "\n" .
                    '  <comment>> derived from:</comment> click $".selector"'
                ,
            ],
            'passed resolved assertion' => [
                'statementLine' => new EncapsulatingStatementLine(
                    $resolvedIsAssertion,
                    Status::SUCCESS
                ),
                'expectedRenderedStatementLine' =>
                    '<icon-success /> $".selector" is "value"' . "\n" .
                    '  <comment>> resolved from:</comment> $page_import_name.elements.selector is "value"'
                ,
            ],
            'passed resolved action' => [
                'statementLine' => new EncapsulatingStatementLine(
                    $resolvedClickAction,
                    Status::SUCCESS
                ),
                'expectedRenderedStatementLine' =>
                    '<icon-success /> click $".selector"' . "\n" .
                    '  <comment>> resolved from:</comment> click $page_import_name.elements.selector'
                ,
            ],
            'failed derived exists assertion' => [
                'statementLine' => new EncapsulatingStatementLine(
                    new DerivedValueOperationAssertion($clickAction, '$".selector"', 'exists'),
                    Status::FAILURE
                ),
                'expectedRenderedStatementLine' =>
                    '<icon-failure /> <highlighted-failure>$".selector" exists</highlighted-failure>' . "\n" .
                    '  <comment>> derived from:</comment> click $".selector"'
                ,
            ],
            'failed resolved assertion' => [
                'statementLine' => new EncapsulatingStatementLine(
                    $resolvedIsAssertion,
                    Status::FAILURE
                ),
                'expectedRenderedStatementLine' =>
                    '<icon-failure /> <highlighted-failure>$".selector" is "value"</highlighted-failure>' . "\n" .
                    '  <comment>> resolved from:</comment> $page_import_name.elements.selector is "value"'
                ,
            ],
            'failed resolved action' => [
                'statementLine' => new EncapsulatingStatementLine(
                    $resolvedClickAction,
                    Status::FAILURE
                ),
                'expectedRenderedStatementLine' =>
                    '<icon-failure /> <highlighted-failure>click $".selector"</highlighted-failure>' . "\n" .
                    '  <comment>> resolved from:</comment> click $page_import_name.elements.selector'
                ,
            ],
            'failed derived resolved exists assertion' => [
                'statementLine' => new EncapsulatingStatementLine(
                    new DerivedValueOperationAssertion($resolvedClickAction, '$".selector"', 'exists'),
                    Status::FAILURE
                ),
                'expectedRenderedStatementLine' =>
                    '<icon-failure /> <highlighted-failure>$".selector" exists</highlighted-failure>' . "\n" .
                    '  <comment>> derived from:</comment> click $".selector"' . "\n" .
                    '  <comment>> resolved from:</comment> click $page_import_name.elements.selector'
                ,
            ],
        ];
    }
}
