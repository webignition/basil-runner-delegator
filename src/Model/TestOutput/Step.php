<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\TestOutput;

use PHPUnit\Runner\BaseTestRunner;
use webignition\BaseBasilTestCase\BasilTestCaseInterface;
use webignition\BasilModels\StatementInterface;

class Step
{
    private BasilTestCaseInterface $test;

    /**
     * @var StatementLine[]
     */
    private array $completedStatementLines = [];

    private ?StatementLine $failedStatementLine = null;

    public function __construct(BasilTestCaseInterface $test)
    {
        $this->test = $test;

        $completedStatements = $test->getHandledStatements();
        $failedStatement = null;

        if (BaseTestRunner::STATUS_PASSED !== $test->getStatus()) {
            $failedStatement = array_pop($completedStatements);
        }

        foreach ($completedStatements as $completedStatement) {
            $this->completedStatementLines[] = StatementLine::createPassedStatementLine($completedStatement);
        }

        if ($failedStatement instanceof StatementInterface) {
            $this->failedStatementLine = StatementLine::createFailedStatementLine($failedStatement);
        }
    }

    public function getName(): string
    {
        return $this->test->getBasilStepName();
    }

    public function getStatus(): int
    {
        return $this->test->getStatus();
    }

    /**
     * @return StatementLine[]
     */
    public function getCompletedStatementLines(): array
    {
        return $this->completedStatementLines;
    }

    public function getFailedStatementLine(): ?StatementLine
    {
        return $this->failedStatementLine;
    }

    public function getExpectedValue(): string
    {
        return (string) $this->test->getExpectedValue();
    }

    public function getActualValue(): string
    {
        return (string) $this->test->getExaminedValue();
    }
}
