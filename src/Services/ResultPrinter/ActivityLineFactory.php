<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter;

use PHPUnit\Runner\BaseTestRunner;
use webignition\BaseBasilTestCase\BasilTestCaseInterface;
use webignition\BasilModels\Assertion\DerivedAssertionInterface;
use webignition\BasilModels\StatementInterface;
use webignition\BasilRunner\Model\ActivityLine;
use webignition\BasilRunner\Model\TerminalString\Style;
use webignition\BasilRunner\Model\TerminalString\TerminalString;

class ActivityLineFactory
{
    private const DEFAULT_ICON = '?';

    /**
     * @var array<int, string>
     */
    private $icons = [
        BaseTestRunner::STATUS_PASSED => '✓',
        BaseTestRunner::STATUS_FAILURE => 'x',
    ];

    public function createStepNameLine(BasilTestCaseInterface $test): ActivityLine
    {
        $testEndStatus = $test->getStatus();

        $icon = $this->icons[$testEndStatus] ?? self::DEFAULT_ICON;
        $content = $test->getBasilStepName();

        $style = new Style([
            Style::FOREGROUND_COLOUR => BaseTestRunner::STATUS_PASSED === $testEndStatus
                ? Style::COLOUR_GREEN
                : Style::COLOUR_RED,
        ]);

        return new ActivityLine(
            new TerminalString($icon, $style),
            new TerminalString($content, $style)
        );
    }

    public function createCompletedStatementLine(StatementInterface $statement): ActivityLine
    {
        return $this->createStatementLine(
            $statement,
            function (StatementInterface $statement): ActivityLine {
                return new ActivityLine(
                    new TerminalString(
                        $this->icons[BaseTestRunner::STATUS_PASSED],
                        new Style([
                            Style::FOREGROUND_COLOUR => Style::COLOUR_GREEN,
                        ])
                    ),
                    new TerminalString($statement->getSource())
                );
            }
        );
    }

    public function createFailedStatementLine(StatementInterface $statement): ActivityLine
    {
        return $this->createStatementLine(
            $statement,
            function (StatementInterface $statement): ActivityLine {
                return new ActivityLine(
                    new TerminalString(
                        $this->icons[BaseTestRunner::STATUS_FAILURE],
                        new Style([
                            Style::FOREGROUND_COLOUR => Style::COLOUR_RED,
                        ])
                    ),
                    new TerminalString(
                        $statement->getSource(),
                        new Style([
                            Style::FOREGROUND_COLOUR => Style::COLOUR_WHITE,
                            Style::BACKGROUND_COLOUR => Style::COLOUR_RED,
                        ])
                    )
                );
            }
        );
    }

    private function createStatementLine(StatementInterface $statement, callable $activityLineCreator): ActivityLine
    {
        $sourceStatementActivityLine = null;

        if ($statement instanceof DerivedAssertionInterface) {
            $sourceStatement = $statement->getSourceStatement();

            $sourceStatementActivityLine = new ActivityLine(
                new TerminalString(
                    '> derived from:',
                    new Style([
                        Style::FOREGROUND_COLOUR => Style::COLOUR_YELLOW,
                    ])
                ),
                new TerminalString($sourceStatement->getSource())
            );
        }

        /* @var ActivityLine $statementActivityLine */
        $statementActivityLine = $activityLineCreator($statement);

        if ($sourceStatementActivityLine instanceof ActivityLine) {
            $statementActivityLine->addChild($sourceStatementActivityLine);
        }

        return $statementActivityLine;
    }
}
