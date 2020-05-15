<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter\StepRenderer;

use webignition\BasilModels\Assertion\DerivedElementExistsAssertion;
use webignition\BasilModels\StatementInterface;
use webignition\BasilParser\ActionParser;
use webignition\BasilParser\AssertionParser;
use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;
use webignition\BasilRunner\Services\ResultPrinter\StepRenderer\StatementLineFactory;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class StatementLineFactoryTest extends AbstractBaseTest
{
    /**
     * @var StatementLineFactory
     */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new StatementLineFactory(
            new ConsoleOutputFactory()
        );
    }

    /**
     * @dataProvider createCompletedStatementLineDataProvider
     */
    public function testCreateCompletedStatementLine(StatementInterface $statement, string $expectedActivityLine)
    {
        $this->assertEquals($expectedActivityLine, $this->factory->createCompletedLine($statement));
    }

    public function createCompletedStatementLineDataProvider(): array
    {
        $actionParser = ActionParser::create();
        $assertionParser = AssertionParser::create();

        $existsAssertion = $assertionParser->parse('$".selector" exists');
        $clickAction = $actionParser->parse('click $".selector');

        $consoleOutputFactory = new ConsoleOutputFactory();

        $prefix = $consoleOutputFactory->createSuccess('✓');

        return [
            'exists assertion' => [
                'statement' => $assertionParser->parse('$".selector" exists'),
                'expectedActivityLine' => $prefix . ' ' . $existsAssertion->getSource(),
            ],
            'click action assertion' => [
                'statement' => $clickAction,
                'expectedActivityLine' => $prefix . ' ' . $clickAction->getSource(),
            ],
            'derived exists assertion' => [
                'statement' => new DerivedElementExistsAssertion($clickAction, '$".selector"'),
                'expectedActivityLine' =>
                    $prefix . ' ' . $existsAssertion->getSource() . "\n" .
                    '  ' . $consoleOutputFactory->createComment('> derived from:') . ' ' . $clickAction->getSource()
                ,
            ],
        ];
    }

    /**
     * @dataProvider createFailedStatementLineDataProvider
     */
    public function testCreateFailedStatementLine(StatementInterface $statement, string $expectedActivityLine)
    {
        $this->assertEquals($expectedActivityLine, $this->factory->createFailedLine($statement));
    }

    public function createFailedStatementLineDataProvider(): array
    {
        $actionParser = ActionParser::create();
        $assertionParser = AssertionParser::create();

        $existsAssertion = $assertionParser->parse('$".selector" exists');
        $clickAction = $actionParser->parse('click $".selector');

        $consoleOutputFactory = new ConsoleOutputFactory();

        $prefix = $consoleOutputFactory->createFailure('x');

        return [
            'exists assertion' => [
                'statement' => $assertionParser->parse('$".selector" exists'),
                'expectedActivityLine' =>
                    $prefix . ' ' .
                    $consoleOutputFactory->createHighlightedFailure($existsAssertion->getSource())
                ,
            ],
            'click action assertion' => [
                'statement' => $clickAction,
                'expectedActivityLine' =>
                    $prefix . ' ' .
                    $consoleOutputFactory->createHighlightedFailure($clickAction->getSource())
                ,
            ],
            'derived exists assertion' => [
                'statement' => new DerivedElementExistsAssertion(
                    $clickAction,
                    '$".selector"'
                ),
                'expectedActivityLine' =>
                    $prefix . ' ' .
                    $consoleOutputFactory->createHighlightedFailure($existsAssertion->getSource()) . "\n" .
                    '  ' . $consoleOutputFactory->createComment('> derived from:') . ' ' . $clickAction->getSource()
                ,
            ],
        ];
    }
}
