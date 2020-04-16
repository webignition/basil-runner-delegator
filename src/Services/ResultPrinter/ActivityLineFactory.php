<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter;

use PHPUnit\Runner\BaseTestRunner;
use webignition\BaseBasilTestCase\BasilTestCaseInterface;
use webignition\BasilModels\Assertion\DerivedAssertionInterface;
use webignition\BasilModels\StatementInterface;
use webignition\BasilRunner\Model\ActivityLine;

class ActivityLineFactory
{
    private const DEFAULT_ICON = '?';

    private $consoleOutputFactory;

    /**
     * @var array<int, string>
     */
    private $icons = [
        BaseTestRunner::STATUS_PASSED => '✓',
        BaseTestRunner::STATUS_FAILURE => 'x',
    ];

    public function __construct(ConsoleOutputFactory $consoleOutputFactory)
    {
        $this->consoleOutputFactory = $consoleOutputFactory;
    }

    public function createStepNameLine(BasilTestCaseInterface $test): ActivityLine
    {
        $testEndStatus = $test->getStatus();

        $icon = $this->icons[$testEndStatus] ?? self::DEFAULT_ICON;
        $content = $test->getBasilStepName();

        $styledIcon = $testEndStatus === BaseTestRunner::STATUS_PASSED
            ? $this->consoleOutputFactory->createSuccess($icon)
            : $this->consoleOutputFactory->createFailure($icon);

        $styledContent = $testEndStatus === BaseTestRunner::STATUS_PASSED
            ? $this->consoleOutputFactory->createSuccess($content)
            : $this->consoleOutputFactory->createFailure($content);

        return new ActivityLine($styledIcon, $styledContent);
    }

    public function createCompletedStatementLine(StatementInterface $statement): ActivityLine
    {
        return $this->createStatementLine(
            $statement,
            function (StatementInterface $statement): ActivityLine {
                return new ActivityLine(
                    $this->consoleOutputFactory->createSuccess($this->icons[BaseTestRunner::STATUS_PASSED]),
                    $statement->getSource()
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
                    $this->consoleOutputFactory->createFailure($this->icons[BaseTestRunner::STATUS_FAILURE]),
                    $this->consoleOutputFactory->createHighlightedFailure($statement->getSource())
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
                $this->consoleOutputFactory->createComment('> derived from:'),
                $sourceStatement->getSource()
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
