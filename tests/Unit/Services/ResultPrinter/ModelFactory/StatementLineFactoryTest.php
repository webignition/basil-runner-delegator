<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter\ModelFactory;

use webignition\BasilModels\Assertion\DerivedValueOperationAssertion;
use webignition\BasilParser\ActionParser;
use webignition\BasilParser\AssertionParser;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\BasilRunner\Model\TestOutput\StatementLine;
use webignition\BasilRunner\Model\ResultPrinter\StatementLine\StatementLine as RenderableStatementLine;
use webignition\BasilRunner\Model\TestOutput\Status;
use webignition\BasilRunner\Services\ResultPrinter\ModelFactory\StatementLineFactory;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class StatementLineFactoryTest extends AbstractBaseTest
{
    private StatementLineFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new StatementLineFactory();
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testRender(StatementLine $statementLine, RenderableInterface $expectedModel)
    {
        $this->assertEquals($expectedModel, $this->factory->create($statementLine));
    }

    public function createDataProvider(): array
    {
        $actionParser = ActionParser::create();
        $assertionParser = AssertionParser::create();

        $existsAssertion = $assertionParser->parse('$".selector" exists');
        $clickAction = $actionParser->parse('click $".selector"');

        return [
            'passed exists assertion' => [
                'statementLine' => StatementLine::createPassedStatementLine($existsAssertion),
                'expectedModel' => new RenderableStatementLine($existsAssertion, Status::SUCCESS),
            ],
            'failed derived exists assertion' => [
                'statementLine' => StatementLine::createFailedStatementLine(
                    new DerivedValueOperationAssertion($clickAction, '$".selector"', 'exists')
                ),
                'expectedModel' => new RenderableStatementLine(
                    new DerivedValueOperationAssertion($clickAction, '$".selector"', 'exists'),
                    Status::FAILURE
                ),
            ],
        ];
    }
}
