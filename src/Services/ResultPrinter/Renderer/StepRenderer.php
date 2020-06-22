<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\Renderer;

use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\DataSet\DataSetInterface;
use webignition\BasilRunner\Model\ResultPrinter\DataSet\KeyValueCollection;
use webignition\BasilRunner\Model\ResultPrinter\IndentedContent;
use webignition\BasilRunner\Model\ResultPrinter\Literal;
use webignition\BasilRunner\Model\ResultPrinter\RenderableCollection;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\BasilRunner\Model\ResultPrinter\StatementLine\StatementLine as RenderableStatementLine;
use webignition\BasilRunner\Model\ResultPrinter\StepName;
use webignition\BasilRunner\Model\TestOutput\StatementLine;
use webignition\BasilRunner\Model\TestOutput\Step;
use webignition\BasilRunner\Services\ResultPrinter\ModelFactory\ExceptionFactory;
use webignition\BasilRunner\Services\ResultPrinter\ModelFactory\SummaryFactory;

class StepRenderer
{
    private SummaryFactory $summaryFactory;
    private ExceptionFactory $exceptionFactory;

    public function __construct(SummaryFactory $summaryFactory, ExceptionFactory $exceptionFactory)
    {
        $this->summaryFactory = $summaryFactory;
        $this->exceptionFactory = $exceptionFactory;
    }

    public function render(Step $step): string
    {
        $stepName = new StepName($step);

        $content = $stepName->render() . "\n";

        $dataSet = $step->getCurrentDataSet();
        if ($dataSet instanceof DataSetInterface) {
            $keyValueCollection = new IndentedContent(KeyValueCollection::fromDataSet($dataSet), 2);
            $content .= $keyValueCollection->render() . "\n\n";
        }

        $content .= $this->renderCompletedStatements($step);

        $failedStatementLine = $step->getFailedStatementLine();

        if ($failedStatementLine instanceof StatementLine) {
            if (0 !== count($step->getCompletedStatementLines())) {
                $content .= "\n";
            }

            $content .= $this->renderFailedStatement(
                $failedStatementLine,
                $step->getExpectedValue(),
                $step->getActualValue()
            );
        }

        $lastException = $step->getLastException();
        if ($lastException instanceof \Throwable) {
            $exceptionModel = $this->exceptionFactory->create($lastException);
            $exceptionContent = new IndentedContent(
                new Literal(
                    '* ' . $exceptionModel->render()
                )
            );

            $content .= "\n" . $exceptionContent->render();
        }

        return $content;
    }

    private function renderCompletedStatements(Step $step): string
    {
        $renderableStatements = [];
        foreach ($step->getCompletedStatementLines() as $completedStatementLine) {
            if (false === $completedStatementLine->getIsDerived()) {
                $renderableStatements[] = RenderableStatementLine::fromOutputStatementLine($completedStatementLine);
            }
        }

        $statementCollection = new IndentedContent(new RenderableCollection($renderableStatements));

        return $statementCollection->render();
    }

    private function renderFailedStatement(
        StatementLine $statementLine,
        string $expectedValue,
        string $actualValue
    ): string {
        $renderableStatement = RenderableStatementLine::fromOutputStatementLine($statementLine);

        $statement = $statementLine->getStatement();
        if ($statement instanceof AssertionInterface) {
            $summaryModel = $this->summaryFactory->create(
                $statement,
                $expectedValue,
                $actualValue
            );

            $renderableStatement = $renderableStatement->withFailureSummary($summaryModel);
        }

        $renderableStatement = new IndentedContent($renderableStatement);

        return $renderableStatement->render();
    }
}
