<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter;

use webignition\BaseBasilTestCase\BasilTestCaseInterface;
use webignition\BasilModels\Assertion\DerivedElementExistsAssertion;
use webignition\BasilModels\StatementInterface;
use webignition\BasilParser\ActionParser;
use webignition\BasilParser\AssertionParser;
use webignition\BasilRunner\Model\ActivityLine;
use webignition\BasilRunner\Services\ResultPrinter\ActivityLineFactory;
use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class ActivityLineFactoryTest extends AbstractBaseTest
{
    /**
     * @var ActivityLineFactory
     */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new ActivityLineFactory(
            new ConsoleOutputFactory()
        );
    }

    /**
     * @dataProvider createStepNameLineDataProvider
     */
    public function testCreateStepNameLine(BasilTestCaseInterface $test, ActivityLine $expectedActivityLine)
    {
        $this->assertEquals($expectedActivityLine, $this->factory->createStepNameLine($test));
    }

    public function createStepNameLineDataProvider(): array
    {
        $consoleOutputFactory = new ConsoleOutputFactory();

        return [
            'passed' => [
                'test' => $this->createTest(0, 'passed step name'),
                'expectedActivityLine' => new ActivityLine(
                    $consoleOutputFactory->createSuccess('✓'),
                    $consoleOutputFactory->createSuccess('passed step name')
                ),
            ],
            'failed' => [
                'test' => $this->createTest(3, 'failed step name'),
                'expectedActivityLine' => new ActivityLine(
                    $consoleOutputFactory->createFailure('x'),
                    $consoleOutputFactory->createFailure('failed step name')
                ),
            ],
            'unknown' => [
                'test' => $this->createTest(1, 'unknown step name'),
                'expectedActivityLine' => new ActivityLine(
                    $consoleOutputFactory->createFailure('?'),
                    $consoleOutputFactory->createFailure('unknown step name')
                ),
            ],
        ];
    }

    /**
     * @dataProvider createCompletedStatementLineDataProvider
     */
    public function testCreateCompletedStatementLine(StatementInterface $statement, ActivityLine $expectedActivityLine)
    {
        $this->assertEquals($expectedActivityLine, $this->factory->createCompletedStatementLine($statement));
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
                'expectedActivityLine' => new ActivityLine($prefix, $existsAssertion->getSource()),
            ],
            'click action assertion' => [
                'statement' => $clickAction,
                'expectedActivityLine' => new ActivityLine($prefix, $clickAction->getSource()),
            ],
            'derived exists assertion' => [
                'statement' => new DerivedElementExistsAssertion($clickAction, '$".selector"'),
                'expectedActivityLine' => $this->addChildToActivityLine(
                    new ActivityLine(
                        $prefix,
                        $existsAssertion->getSource()
                    ),
                    new ActivityLine(
                        $consoleOutputFactory->createComment('> derived from:'),
                        $clickAction->getSource()
                    )
                ),
            ],
        ];
    }

    /**
     * @dataProvider createFailedStatementLineDataProvider
     */
    public function testCreateFailedStatementLine(StatementInterface $statement, ActivityLine $expectedActivityLine)
    {
        $this->assertEquals($expectedActivityLine, $this->factory->createFailedStatementLine($statement));
    }

    public function createFailedStatementLineDataProvider(): array
    {
        $actionParser = ActionParser::create();
        $assertionParser = AssertionParser::create();

        $existsAssertion = $assertionParser->parse('$".selector" exists');
        $clickAction = $actionParser->parse('click $".selector');

        $consoleOutputFactory = new ConsoleOutputFactory();

        $prefix = $consoleOutputFactory->createFailure('x');

//        $contentStyle = new Style([
//            Style::FOREGROUND_COLOUR => Style::COLOUR_WHITE,
//            Style::BACKGROUND_COLOUR => Style::COLOUR_RED,
//        ]);

        return [
            'exists assertion' => [
                'statement' => $assertionParser->parse('$".selector" exists'),
                'expectedActivityLine' => new ActivityLine(
                    $prefix,
                    $consoleOutputFactory->createHighlightedFailure($existsAssertion->getSource())
                ),
            ],
            'click action assertion' => [
                'statement' => $clickAction,
                'expectedActivityLine' => new ActivityLine(
                    $prefix,
                    $consoleOutputFactory->createHighlightedFailure($clickAction->getSource())
                ),
            ],
            'derived exists assertion' => [
                'statement' => new DerivedElementExistsAssertion(
                    $clickAction,
                    '$".selector"'
                ),
                'expectedActivityLine' => $this->addChildToActivityLine(
                    new ActivityLine(
                        $prefix,
                        $consoleOutputFactory->createHighlightedFailure($existsAssertion->getSource())
                    ),
                    new ActivityLine(
                        $consoleOutputFactory->createComment('> derived from:'),
                        $clickAction->getSource()
                    )
                ),
            ],
        ];
    }

    private function createTest(int $status, string $basilStepName): BasilTestCaseInterface
    {
        $test = \Mockery::mock(BasilTestCaseInterface::class);
        $test
            ->shouldReceive('getStatus')
            ->andReturn($status);

        $test
            ->shouldReceive('getBasilStepName')
            ->andReturn($basilStepName);

        return $test;
    }

    private function addChildToActivityLine(ActivityLine $activityLine, ActivityLine $child): ActivityLine
    {
        $activityLine->addChild($child);

        return $activityLine;
    }
}
