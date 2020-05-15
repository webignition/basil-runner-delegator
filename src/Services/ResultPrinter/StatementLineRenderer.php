<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter;

use PHPUnit\Runner\BaseTestRunner;
use webignition\BasilModels\Assertion\DerivedAssertionInterface;
use webignition\BasilModels\StatementInterface;
use webignition\BasilRunner\Model\TestOutput\IconMap;
use webignition\BasilRunner\Model\TestOutput\StatementLine;

class StatementLineRenderer
{
    private $consoleOutputFactory;

    public function __construct(ConsoleOutputFactory $consoleOutputFactory)
    {
        $this->consoleOutputFactory = $consoleOutputFactory;
    }

    public function render(StatementLine $statementLine): string
    {
        $statement = $statementLine->getStatement();

        if ($statementLine->getHasPassed()) {
            return $this->renderedPassedStatement($statement);
        }

        return $this->renderedFailedStatement($statement);
    }

    private function renderedPassedStatement(StatementInterface $statement): string
    {
        return (string) $this->create(
            $statement,
            function (StatementInterface $statement): string {
                return
                    $this->consoleOutputFactory->createSuccess(IconMap::get(BaseTestRunner::STATUS_PASSED)) . ' ' .
                    $statement->getSource()
                    ;
            }
        );
    }

    private function renderedFailedStatement(StatementInterface $statement): string
    {
        return (string) $this->create(
            $statement,
            function (StatementInterface $statement): string {
                return
                    $this->consoleOutputFactory->createFailure(IconMap::get(BaseTestRunner::STATUS_FAILURE)) . ' ' .
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
