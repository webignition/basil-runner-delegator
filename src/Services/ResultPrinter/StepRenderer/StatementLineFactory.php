<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\StepRenderer;

use PHPUnit\Runner\BaseTestRunner;
use webignition\BasilModels\Assertion\DerivedAssertionInterface;
use webignition\BasilModels\StatementInterface;
use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;

class StatementLineFactory
{
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

    public function createCompletedLine(StatementInterface $statement): string
    {
        return (string) $this->create(
            $statement,
            function (StatementInterface $statement): string {
                return
                    $this->consoleOutputFactory->createSuccess($this->icons[BaseTestRunner::STATUS_PASSED]) . ' ' .
                    $statement->getSource()
                ;
            }
        );
    }

    public function createFailedLine(StatementInterface $statement): string
    {
        return (string) $this->create(
            $statement,
            function (StatementInterface $statement): string {
                return
                    $this->consoleOutputFactory->createFailure($this->icons[BaseTestRunner::STATUS_FAILURE]) . ' ' .
                    $this->consoleOutputFactory->createHighlightedFailure($statement->getSource())
                ;
            }
        );
    }

    private function create(StatementInterface $statement, callable $activityLineCreator): string
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
