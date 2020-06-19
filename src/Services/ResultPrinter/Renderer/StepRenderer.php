<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\Renderer;

use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\DataSet\DataSetInterface;
use webignition\BasilRunner\Model\ResultPrinter\DataSet\KeyValueCollection;
use webignition\BasilRunner\Model\ResultPrinter\StepName;
use webignition\BasilRunner\Model\TestOutput\StatementLine;
use webignition\BasilRunner\Model\TestOutput\Step;
use webignition\BasilRunner\Services\ResultPrinter\FailedAssertion\SummaryHandler;
use webignition\BasilRunner\Services\ResultPrinter\ModelFactory\ExceptionFactory;

class StepRenderer
{
    private const INDENT = '  ';

    private StatementLineRenderer $statementLineRenderer;
    private SummaryHandler $summaryHandler;
    private ExceptionFactory $exceptionFactory;

    public function __construct(
        StatementLineRenderer $statementLineRenderer,
        SummaryHandler $summaryHandler,
        ExceptionFactory $exceptionFactory
    ) {
        $this->statementLineRenderer = $statementLineRenderer;
        $this->summaryHandler = $summaryHandler;
        $this->exceptionFactory = $exceptionFactory;
    }

    public function render(Step $step): string
    {
        $stepName = new StepName($step);

        $content = $stepName->render() . "\n";

        $dataSet = $step->getCurrentDataSet();
        if ($dataSet instanceof DataSetInterface) {
            $keyValueCollection = KeyValueCollection::fromDataSet($dataSet);
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
            $exceptionContent = '* ' . $exceptionModel->render();

            $content .= "\n" . $this->indent($exceptionContent, 2);
        }

        return $content;
    }

    private function renderCompletedStatements(Step $step): string
    {
        $renderedStatements = [];

        foreach ($step->getCompletedStatementLines() as $completedStatementLine) {
            if (false === $completedStatementLine->getIsDerived()) {
                $renderedStatements[] = $this->statementLineRenderer->render($completedStatementLine);
            }
        }

        return implode("\n", $renderedStatements);
    }

    private function renderFailedStatement(
        StatementLine $statementLine,
        string $expectedValue,
        string $actualValue
    ): string {
        $content = $this->statementLineRenderer->render($statementLine);
        $summary = null;

        $statement = $statementLine->getStatement();
        if ($statement instanceof AssertionInterface) {
            $summary = $this->summaryHandler->handle(
                $statement,
                $expectedValue,
                $actualValue
            );
        }

        if (is_string($summary)) {
            $content .= "\n";
            $content .= $this->indent($summary, 2);
        }

        return $content;
    }

    private function indent(string $content, int $depth = 1): string
    {
        $indentContent = str_repeat(self::INDENT, $depth);

        $lines = explode("\n", $content);

        array_walk($lines, function (&$line) use ($indentContent) {
            $line = $indentContent . $line;
        });

        return implode("\n", $lines);
    }
}
