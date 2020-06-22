<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter\ModelFactory;

use webignition\BaseBasilTestCase\BasilTestCaseInterface;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\DataSet\DataSetInterface;
use webignition\BasilModels\StatementInterface;
use webignition\BasilParser\ActionParser;
use webignition\BasilParser\AssertionParser;
use webignition\BasilRunner\Model\ResultPrinter\Literal;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\BasilRunner\Model\ResultPrinter\StatementLine\StatementLine;
use webignition\BasilRunner\Model\ResultPrinter\Step\Step;
use webignition\BasilRunner\Model\TestOutput\Status;
use webignition\BasilRunner\Services\ResultPrinter\ModelFactory\ExceptionFactory;
use webignition\BasilRunner\Services\ResultPrinter\ModelFactory\StepFactory;
use webignition\BasilRunner\Services\ResultPrinter\ModelFactory\SummaryFactory;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class StepFactoryTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testCreate(StepFactory $factory, BasilTestCaseInterface $test, Step $expectedStep)
    {
        $this->assertEquals($expectedStep, $factory->create($test));
    }

    public function renderDataProvider(): array
    {
        $actionParser = ActionParser::create();
        $assertionParser = AssertionParser::create();

        $passedNoStatementsTest = $this->createBasilTestCase(
            Status::SUCCESS,
            'passed, no failed statement line, no last exception',
            [],
            '',
            '',
            null,
            null
        );

        $clickAction = $actionParser->parse('click $".selector"');

        $failedActionTest = $this->createBasilTestCase(
            Status::FAILURE,
            'failed, has failed action statement line',
            [
                $clickAction
            ],
            '',
            '',
            null,
            null
        );

        $existsAssertion = $assertionParser->parse('$".selector" exists');

        $failedAssertionTest = $this->createBasilTestCase(
            Status::FAILURE,
            'failed, has failed assertion statement line',
            [
                $existsAssertion
            ],
            '',
            '',
            null,
            null
        );

        $exception = new \Exception('exception message');

        $failedAssertionTestWithException = $this->createBasilTestCase(
            Status::FAILURE,
            'failed, has failed assertion statement line, has last exception',
            [
                $existsAssertion
            ],
            '',
            '',
            $exception,
            null
        );

        return [
            'passed, no failed statement line, no last exception' => [
                'factory' => StepFactory::createFactory(),
                'test' => $passedNoStatementsTest,
                'expectedStep' => new Step($passedNoStatementsTest),
            ],
            'failed, has failed action statement line' => [
                'factory' => StepFactory::createFactory(),
                'test' => $failedActionTest,
                'expectedStep' => $this->setFailedStatementOnStep(
                    new Step($failedActionTest),
                    new StatementLine($clickAction, Status::FAILURE)
                ),
            ],
            'failed, has failed assertion statement line' => [
                'factory' => new StepFactory(
                    $this->createSummaryFactory(
                        $existsAssertion,
                        '',
                        '',
                        new Literal('failed assertion summary')
                    ),
                    new ExceptionFactory()
                ),
                'test' => $failedAssertionTest,
                'expectedStep' => $this->setFailedStatementOnStep(
                    new Step($failedAssertionTest),
                    (new StatementLine(
                        $existsAssertion,
                        Status::FAILURE
                    ))->withFailureSummary(new Literal('failed assertion summary'))
                ),
            ],
            'failed, has failed assertion statement line, has last exception' => [
                'factory' => new StepFactory(
                    $this->createSummaryFactory(
                        $existsAssertion,
                        '',
                        '',
                        new Literal('failed assertion summary')
                    ),
                    $this->createExceptionFactory(
                        $exception,
                        new Literal('exception content')
                    )
                ),
                'test' => $failedAssertionTestWithException,
                'expectedStep' => $this->setLastExceptionOnStep(
                    $this->setFailedStatementOnStep(
                        new Step($failedAssertionTestWithException),
                        (new StatementLine(
                            $existsAssertion,
                            Status::FAILURE
                        ))->withFailureSummary(new Literal('failed assertion summary'))
                    ),
                    new Literal('* exception content')
                ),
            ],
        ];
    }

    private function createSummaryFactory(
        AssertionInterface $assertion,
        string $expectedValue,
        string $actualValue,
        ?RenderableInterface $return
    ): SummaryFactory {
        $summaryFactory = \Mockery::mock(SummaryFactory::class);

        $summaryFactory
            ->shouldReceive('create')
            ->with($assertion, $expectedValue, $actualValue)
            ->andReturn($return);

        return $summaryFactory;
    }

    private function createExceptionFactory(\Throwable $exception, RenderableInterface $return): ExceptionFactory
    {
        $exceptionFactory = \Mockery::mock(ExceptionFactory::class);

        $exceptionFactory
            ->shouldReceive('create')
            ->with($exception)
            ->andReturn($return);

        return $exceptionFactory;
    }

    /**
     * @param int $status
     * @param string $name
     * @param StatementInterface[] $handledStatements
     * @param StatementInterface|null $failedStatement
     * @param string $expectedValue
     * @param string $examinedValue
     * @param \Throwable|null $lastException
     * @param DataSetInterface|null $dataSet
     *
     * @return BasilTestCaseInterface
     */
    private function createBasilTestCase(
        int $status,
        string $name,
        array $handledStatements,
        string $expectedValue,
        string $examinedValue,
        ?\Throwable $lastException,
        ?DataSetInterface $dataSet
    ): BasilTestCaseInterface {
        $step = \Mockery::mock(BasilTestCaseInterface::class);

        $step
            ->shouldReceive('getStatus')
            ->andReturn($status);

        $step
            ->shouldReceive('getBasilStepName')
            ->andReturn($name);

        $step
            ->shouldReceive('getHandledStatements')
            ->andReturn($handledStatements);

        $step
            ->shouldReceive('getExpectedValue')
            ->andReturn($expectedValue);

        $step
            ->shouldReceive('getExaminedValue')
            ->andReturn($examinedValue);

        $step
            ->shouldReceive('getLastException')
            ->andReturn($lastException);

        $step
            ->shouldReceive('getCurrentDataSet')
            ->andReturn($dataSet);

        return $step;
    }

    private function setFailedStatementOnStep(Step $step, RenderableInterface $renderable): Step
    {
        $step->setFailedStatement($renderable);

        return $step;
    }

    private function setLastExceptionOnStep(Step $step, RenderableInterface $renderable): Step
    {
        $step->setLastException($renderable);

        return $step;
    }
}
