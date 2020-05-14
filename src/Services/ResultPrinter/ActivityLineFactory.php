<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter;

use PHPUnit\Runner\BaseTestRunner;
use webignition\BaseBasilTestCase\BasilTestCaseInterface;
use webignition\BasilModels\Assertion\DerivedAssertionInterface;
use webignition\BasilModels\StatementInterface;

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

    public function createStepNameLine(BasilTestCaseInterface $test): string
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

        return $styledIcon . ' ' . $styledContent;
    }

    public function createCompletedStatementLine(StatementInterface $statement): string
    {
        return (string) $this->createStatementLine(
            $statement,
            function (StatementInterface $statement): string {
                return
                    $this->consoleOutputFactory->createSuccess($this->icons[BaseTestRunner::STATUS_PASSED]) . ' ' .
                    $statement->getSource()
                ;
            }
        );
    }

    public function createFailedStatementLine(StatementInterface $statement): string
    {
        return (string) $this->createStatementLine(
            $statement,
            function (StatementInterface $statement): string {
                return
                    $this->consoleOutputFactory->createFailure($this->icons[BaseTestRunner::STATUS_FAILURE]) . ' ' .
                    $this->consoleOutputFactory->createHighlightedFailure($statement->getSource())
                ;
            }
        );
    }

    private function createStatementLine(StatementInterface $statement, callable $activityLineCreator): string
    {
        $sourceStatementActivityLine = null;

        if ($statement instanceof DerivedAssertionInterface) {
            $sourceStatement = $statement->getSourceStatement();

            $sourceStatementActivityLine =
                $this->consoleOutputFactory->createComment('> derived from:') . ' ' . $sourceStatement->getSource();
        }

        /* @var string $statementActivityLine */
        $statementActivityLine = $activityLineCreator($statement);

        if (null !== $sourceStatementActivityLine) {
            $statementActivityLine .= "\n" . '  ' . $sourceStatementActivityLine;
        }

        return (string) $statementActivityLine;
    }
}
