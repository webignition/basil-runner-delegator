<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter\StatementLine;

use webignition\BasilModels\Action\Action;
use webignition\BasilModels\Action\ResolvedAction;
use webignition\BasilModels\Assertion\Assertion;
use webignition\BasilModels\Assertion\DerivedValueOperationAssertion;
use webignition\BasilModels\Assertion\ResolvedAssertion;
use webignition\BasilRunner\Model\ResultPrinter\Literal;
use webignition\BasilRunner\Model\ResultPrinter\StatementLine\StatementLine;
use webignition\BasilRunner\Model\TestOutput\Status;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class StatementLineTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(StatementLine $statementLine, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $statementLine->render());
    }

    public function renderDataProvider(): array
    {
        $clickAction = new Action(
            'click $".selector"',
            'click',
            '$".selector'
        );
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
            'success, non-derived' => [
                'statementLine' => new StatementLine(
                    $clickAction,
                    Status::SUCCESS
                ),
                'expectedRenderedString' => '<icon-success /> click $".selector"',
            ],
            'failure, non-derived' => [
                'statementLine' => new StatementLine(
                    $clickAction,
                    Status::FAILURE
                ),
                'expectedRenderedString' =>
                    '<icon-failure /> <highlighted-failure>click $".selector"</highlighted-failure>',
            ],
            'success, derived exists assertion' => [
                'statementLine' => new StatementLine(
                    new DerivedValueOperationAssertion($clickAction, '$".selector"', 'exists'),
                    Status::SUCCESS
                ),
                'expectedRenderedStatementLine' =>
                    '<icon-success /> $".selector" exists' . "\n" .
                    '  <comment>> derived from:</comment> click $".selector"'
                ,
            ],
            'success, resolved assertion' => [
                'statementLine' => new StatementLine(
                    $resolvedIsAssertion,
                    Status::SUCCESS
                ),
                'expectedRenderedStatementLine' =>
                    '<icon-success /> $".selector" is "value"' . "\n" .
                    '  <comment>> resolved from:</comment> $page_import_name.elements.selector is "value"'
                ,
            ],
            'success, resolved action' => [
                'statementLine' => new StatementLine(
                    $resolvedClickAction,
                    Status::SUCCESS
                ),
                'expectedRenderedStatementLine' =>
                    '<icon-success /> click $".selector"' . "\n" .
                    '  <comment>> resolved from:</comment> click $page_import_name.elements.selector'
                ,
            ],
            'failure, derived exists assertion' => [
                'statementLine' => new StatementLine(
                    new DerivedValueOperationAssertion($clickAction, '$".selector"', 'exists'),
                    Status::FAILURE
                ),
                'expectedRenderedStatementLine' =>
                    '<icon-failure /> <highlighted-failure>$".selector" exists</highlighted-failure>' . "\n" .
                    '  <comment>> derived from:</comment> click $".selector"'
                ,
            ],
            'failure, resolved assertion' => [
                'statementLine' => new StatementLine(
                    $resolvedIsAssertion,
                    Status::FAILURE
                ),
                'expectedRenderedStatementLine' =>
                    '<icon-failure /> <highlighted-failure>$".selector" is "value"</highlighted-failure>' . "\n" .
                    '  <comment>> resolved from:</comment> $page_import_name.elements.selector is "value"'
                ,
            ],
            'failure, resolved action' => [
                'statementLine' => new StatementLine(
                    $resolvedClickAction,
                    Status::FAILURE
                ),
                'expectedRenderedStatementLine' =>
                    '<icon-failure /> <highlighted-failure>click $".selector"</highlighted-failure>' . "\n" .
                    '  <comment>> resolved from:</comment> click $page_import_name.elements.selector'
                ,
            ],
            'failure, derived resolved exists assertion' => [
                'statementLine' => new StatementLine(
                    new DerivedValueOperationAssertion($resolvedClickAction, '$".selector"', 'exists'),
                    Status::FAILURE
                ),
                'expectedRenderedStatementLine' =>
                    '<icon-failure /> <highlighted-failure>$".selector" exists</highlighted-failure>' . "\n" .
                    '  <comment>> derived from:</comment> click $".selector"' . "\n" .
                    '  <comment>> resolved from:</comment> click $page_import_name.elements.selector'
                ,
            ],
            'failure, non-derived, has failure summary' => [
                'statementLine' =>
                    (new StatementLine(
                        $clickAction,
                        Status::FAILURE
                    ))->withFailureSummary(new Literal('Failure summary content'))
                ,
                'expectedRenderedString' =>
                    '<icon-failure /> <highlighted-failure>click $".selector"</highlighted-failure>' . "\n" .
                    'Failure summary content'
                ,
            ],
        ];
    }
}
