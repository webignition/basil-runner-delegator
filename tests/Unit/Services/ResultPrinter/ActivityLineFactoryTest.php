<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter;

use webignition\BaseBasilTestCase\BasilTestCaseInterface;
use webignition\BasilModels\Assertion\DerivedElementExistsAssertion;
use webignition\BasilModels\StatementInterface;
use webignition\BasilParser\ActionParser;
use webignition\BasilParser\AssertionParser;
use webignition\BasilRunner\Model\ActivityLine;
use webignition\BasilRunner\Model\TerminalString\Style;
use webignition\BasilRunner\Model\TerminalString\TerminalString;
use webignition\BasilRunner\Services\ResultPrinter\ActivityLineFactory;
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

        $this->factory = new ActivityLineFactory();
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
        $passedTestStyle = new Style([
            Style::FOREGROUND_COLOUR => Style::COLOUR_GREEN,
        ]);

        $failedTestStyle = new Style([
            Style::FOREGROUND_COLOUR => Style::COLOUR_RED,
        ]);

        return [
            'passed' => [
                'test' => $this->createTest(0, 'passed step name'),
                'expectedActivityLine' => new ActivityLine(
                    new TerminalString('✓', $passedTestStyle),
                    new TerminalString('passed step name', $passedTestStyle)
                ),
            ],
            'failed' => [
                'test' => $this->createTest(3, 'failed step name'),
                'expectedActivityLine' => new ActivityLine(
                    new TerminalString('x', $failedTestStyle),
                    new TerminalString('failed step name', $failedTestStyle)
                ),
            ],
            'unknown' => [
                'test' => $this->createTest(1, 'unknown step name'),
                'expectedActivityLine' => new ActivityLine(
                    new TerminalString('?', $failedTestStyle),
                    new TerminalString('unknown step name', $failedTestStyle)
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

        $prefix = new TerminalString(
            '✓',
            new Style([
                Style::FOREGROUND_COLOUR => Style::COLOUR_GREEN,
            ])
        );

        return [
            'exists assertion' => [
                'statement' => $assertionParser->parse('$".selector" exists'),
                'expectedActivityLine' => new ActivityLine(
                    $prefix,
                    new TerminalString($existsAssertion->getSource())
                ),
            ],
            'click action assertion' => [
                'statement' => $clickAction,
                'expectedActivityLine' => new ActivityLine(
                    $prefix,
                    new TerminalString($clickAction->getSource())
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
                        new TerminalString($existsAssertion->getSource())
                    ),
                    new ActivityLine(
                        new TerminalString(
                            '> derived from:',
                            new Style([
                                Style::FOREGROUND_COLOUR => Style::COLOUR_YELLOW,
                            ])
                        ),
                        new TerminalString($clickAction->getSource())
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

        $prefix = new TerminalString(
            'x',
            new Style([
                Style::FOREGROUND_COLOUR => Style::COLOUR_RED,
            ])
        );

        $contentStyle = new Style([
            Style::FOREGROUND_COLOUR => Style::COLOUR_WHITE,
            Style::BACKGROUND_COLOUR => Style::COLOUR_RED,
        ]);

        return [
            'exists assertion' => [
                'statement' => $assertionParser->parse('$".selector" exists'),
                'expectedActivityLine' => new ActivityLine(
                    $prefix,
                    new TerminalString($existsAssertion->getSource(), $contentStyle)
                ),
            ],
            'click action assertion' => [
                'statement' => $clickAction,
                'expectedActivityLine' => new ActivityLine(
                    $prefix,
                    new TerminalString($clickAction->getSource(), $contentStyle)
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
                        new TerminalString($existsAssertion->getSource(), $contentStyle)
                    ),
                    new ActivityLine(
                        new TerminalString(
                            '> derived from:',
                            new Style([
                                Style::FOREGROUND_COLOUR => Style::COLOUR_YELLOW,
                            ])
                        ),
                        new TerminalString($clickAction->getSource())
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
