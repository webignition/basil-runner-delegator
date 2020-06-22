<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter\ModelFactory;

use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\DataSet\DataSetInterface;
use webignition\BasilModels\StatementInterface;
use webignition\BasilParser\ActionParser;
use webignition\BasilParser\AssertionParser;
use webignition\BasilRunner\Model\ResultPrinter\Literal;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\BasilRunner\Model\ResultPrinter\StatementLine\StatementLine;
use webignition\BasilRunner\Model\ResultPrinter\Step\Step as RenderableStep;
use webignition\BasilRunner\Model\TestOutput\Status;
use webignition\BasilRunner\Model\TestOutput\Step as OutputStep;
use webignition\BasilRunner\Services\ResultPrinter\ModelFactory\ExceptionFactory;
use webignition\BasilRunner\Services\ResultPrinter\ModelFactory\StepFactory;
use webignition\BasilRunner\Services\ResultPrinter\ModelFactory\SummaryFactory;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class StepFactoryTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testCreate(StepFactory $factory, OutputStep $outputStep, RenderableStep $expectedRenderableStep)
    {
        $this->assertEquals($expectedRenderableStep, $factory->create($outputStep));
    }

    public function renderDataProvider(): array
    {
        $actionParser = ActionParser::create();
        $assertionParser = AssertionParser::create();

        $passedNoStatementsOutputStep = $this->createOutputStep(
            Status::SUCCESS,
            'passed, no failed statement line, no last exception',
            [],
            null,
            '',
            '',
            null,
            null
        );

        $failedActionOutputStatement = $actionParser->parse('click $".selector"');

        $failedActionOutputStep = $this->createOutputStep(
            Status::FAILURE,
            'failed, has failed action statement line',
            [],
            $failedActionOutputStatement,
            '',
            '',
            null,
            null
        );

        $existsAssertion = $assertionParser->parse('$".selector" exists');
        $failedAssertionOutputStatement = $existsAssertion;

        $failedAssertionOutputStep = $this->createOutputStep(
            Status::FAILURE,
            'failed, has failed assertion statement line',
            [],
            $failedAssertionOutputStatement,
            '',
            '',
            null,
            null
        );

        $exception = new \Exception('exception message');

        $failedAssertionWithExceptionOutputStep = $this->createOutputStep(
            Status::FAILURE,
            'failed, has failed assertion statement line, has last exception',
            [],
            $failedAssertionOutputStatement,
            '',
            '',
            $exception,
            null
        );

        return [
            'passed, no failed statement line, no last exception' => [
                'factory' => StepFactory::createFactory(),
                'outputStep' => $passedNoStatementsOutputStep,
                'expectedRenderableStep' => new RenderableStep($passedNoStatementsOutputStep),
            ],
            'failed, has failed action statement line' => [
                'factory' => StepFactory::createFactory(),
                'outputStep' => $failedActionOutputStep,
                'expectedRenderableStep' => $this->setFailedStatementOnStep(
                    new RenderableStep($failedActionOutputStep),
                    new StatementLine($failedActionOutputStatement, Status::FAILURE)
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
                'outputStep' => $failedAssertionOutputStep,
                'expectedRenderableStep' => $this->setFailedStatementOnStep(
                    new RenderableStep($failedAssertionOutputStep),
                    (new StatementLine(
                        $failedAssertionOutputStatement,
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
                'outputStep' => $failedAssertionWithExceptionOutputStep,
                'expectedRenderableStep' => $this->setLastExceptionOnStep(
                    $this->setFailedStatementOnStep(
                        new RenderableStep($failedAssertionWithExceptionOutputStep),
                        (new StatementLine(
                            $failedAssertionOutputStatement,
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
     * @param StatementInterface[] $completedStatements
     * @param StatementInterface|null $failedStatement
     * @param string $expectedValue
     * @param string $actualValue
     * @param \Throwable|null $lastException
     * @param DataSetInterface|null $dataSet
     *
     * @return OutputStep
     */
    private function createOutputStep(
        int $status,
        string $name,
        array $completedStatements,
        ?StatementInterface $failedStatement,
        string $expectedValue,
        string $actualValue,
        ?\Throwable $lastException,
        ?DataSetInterface $dataSet
    ): OutputStep {
        $step = \Mockery::mock(OutputStep::class);

        $step
            ->shouldReceive('getStatus')
            ->andReturn($status);

        $step
            ->shouldReceive('getName')
            ->andReturn($name);

        $step
            ->shouldReceive('getCompletedStatements')
            ->andReturn($completedStatements);

        $step
            ->shouldReceive('getFailedStatement')
            ->andReturn($failedStatement);

        $step
            ->shouldReceive('getExpectedValue')
            ->andReturn($expectedValue);

        $step
            ->shouldReceive('getActualValue')
            ->andReturn($actualValue);

        $step
            ->shouldReceive('getLastException')
            ->andReturn($lastException);

        $step
            ->shouldReceive('getCurrentDataSet')
            ->andReturn($dataSet);

        return $step;
    }

    private function setFailedStatementOnStep(RenderableStep $step, RenderableInterface $renderable): RenderableStep
    {
        $step->setFailedStatement($renderable);

        return $step;
    }

    private function setLastExceptionOnStep(RenderableStep $step, RenderableInterface $renderable): RenderableStep
    {
        $step->setLastException($renderable);

        return $step;
    }
}
