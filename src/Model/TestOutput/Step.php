<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\TestOutput;

use PHPUnit\Runner\BaseTestRunner;
use webignition\BaseBasilTestCase\BasilTestCaseInterface;
use webignition\BasilModels\StatementInterface;

class Step
{
    private $test;

    /**
     * @var StatementInterface[]
     */
    private $completedStatements;

    /**
     * @var StatementInterface|null
     */
    private $failedStatement;

    public function __construct(BasilTestCaseInterface $test)
    {
        $this->test = $test;

        $this->completedStatements = $test->getHandledStatements();

        if (BaseTestRunner::STATUS_PASSED !== $test->getStatus()) {
            $this->failedStatement = array_pop($this->completedStatements);
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
}
