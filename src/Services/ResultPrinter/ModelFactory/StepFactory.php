<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\ModelFactory;

use webignition\BaseBasilTestCase\BasilTestCaseInterface;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\StatementInterface;
use webignition\BasilRunner\Model\ResultPrinter\Literal;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\BasilRunner\Model\ResultPrinter\StatementLine\StatementLine;
use webignition\BasilRunner\Model\ResultPrinter\Step\Step;
use webignition\BasilRunner\Model\TestOutput\Status;

class StepFactory
{
    private SummaryFactory $summaryFactory;
    private ExceptionFactory $exceptionFactory;

    public function __construct(SummaryFactory $summaryFactory, ExceptionFactory $exceptionFactory)
    {
        $this->summaryFactory = $summaryFactory;
        $this->exceptionFactory = $exceptionFactory;
    }

    public static function createFactory(): self
    {
        return new StepFactory(
            SummaryFactory::createFactory(),
            new ExceptionFactory()
        );
    }

    public function create(BasilTestCaseInterface $test): Step
    {
        $step = new Step($test);

        if (Status::FAILURE === $test->getStatus()) {
            $handledStatements = $test->getHandledStatements();
            $failedStatement = array_pop($handledStatements);

            $step->setFailedStatement($this->createFailedStatement(
                $failedStatement,
                (string) $test->getExpectedValue(),
                (string) $test->getExaminedValue()
            ));
        }

        $lastException = $test->getLastException();
        if ($lastException instanceof \Throwable) {
            $exceptionModel = $this->exceptionFactory->create($lastException);
            $exceptionContent = new Literal(
                '* ' . $exceptionModel->render()
            );

            $step->setLastException($exceptionContent);
        }

        return $step;
    }

    private function createFailedStatement(
        StatementInterface $statement,
        string $expectedValue,
        string $actualValue
    ): RenderableInterface {
        $renderableStatement = new StatementLine($statement, Status::FAILURE);

        if ($statement instanceof AssertionInterface) {
            $summaryModel = $this->summaryFactory->create(
                $statement,
                $expectedValue,
                $actualValue
            );

            if ($summaryModel instanceof RenderableInterface) {
                $renderableStatement = $renderableStatement->withFailureSummary($summaryModel);
            }
        }

        return $renderableStatement;
    }
}
