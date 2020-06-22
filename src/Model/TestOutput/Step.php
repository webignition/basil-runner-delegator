<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\TestOutput;

use webignition\BaseBasilTestCase\BasilTestCaseInterface;
use webignition\BasilModels\DataSet\DataSetInterface;
use webignition\BasilModels\StatementInterface;

class Step
{
    private BasilTestCaseInterface $test;

    /**
     * @var StatementInterface[]
     */
    private array $completedStatements;

    private ?StatementInterface $failedStatement = null;

    public function __construct(BasilTestCaseInterface $test)
    {
        $this->test = $test;

        $completedStatements = $test->getHandledStatements();
        $failedStatement = null;

        if (Status::SUCCESS !== $test->getStatus()) {
            $this->failedStatement = array_pop($completedStatements);
        }

        $this->completedStatements = $completedStatements;
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
     * @return StatementInterface[]
     */
    public function getCompletedStatements(): array
    {
        return $this->completedStatements;
    }

    public function getFailedStatement(): ?StatementInterface
    {
        return $this->failedStatement;
    }

    public function getExpectedValue(): string
    {
        return (string) $this->test->getExpectedValue();
    }

    public function getActualValue(): string
    {
        return (string) $this->test->getExaminedValue();
    }

    public function getLastException(): ?\Throwable
    {
        return $this->test->getLastException();
    }

    public function getCurrentDataSet(): ?DataSetInterface
    {
        return $this->test->getCurrentDataSet();
    }
}
