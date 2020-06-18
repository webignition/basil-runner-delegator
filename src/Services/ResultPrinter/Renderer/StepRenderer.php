<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\Renderer;

use PHPUnit\Runner\BaseTestRunner;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\DataSet\DataSetInterface;
use webignition\BasilRunner\Model\ResultPrinter\StepName;
use webignition\BasilRunner\Model\TestOutput\IconMap;
use webignition\BasilRunner\Model\TestOutput\StatementLine;
use webignition\BasilRunner\Model\TestOutput\Status;
use webignition\BasilRunner\Model\TestOutput\Step;
use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;
use webignition\BasilRunner\Services\ResultPrinter\FailedAssertion\SummaryHandler;

class StepRenderer
{
    private const INDENT = '  ';

    private ConsoleOutputFactory $consoleOutputFactory;
    private StatementLineRenderer $statementLineRenderer;
    private SummaryHandler $summaryHandler;
    private ExceptionRenderer $exceptionRenderer;

    public function __construct(
        ConsoleOutputFactory $consoleOutputFactory,
        StatementLineRenderer $statementLineRenderer,
        SummaryHandler $summaryHandler,
        ExceptionRenderer $exceptionRenderer
    ) {
        $this->consoleOutputFactory = $consoleOutputFactory;
        $this->statementLineRenderer = $statementLineRenderer;
        $this->summaryHandler = $summaryHandler;
        $this->exceptionRenderer = $exceptionRenderer;
    }

    public function render(Step $step): string
    {
        $stepName = new StepName($step);

        $content = $stepName->render() . "\n";

        $dataSet = $step->getCurrentDataSet();
        if ($dataSet instanceof DataSetInterface) {
            $dataSetList = '';

            foreach ($dataSet->getData() as $key => $value) {
                $dataSetList .= '$' . $key . ': ' . $this->consoleOutputFactory->createComment($value) . "\n";
            }

            $content .= $this->indent($dataSetList, 3) . "\n";
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
            $exceptionContent = $this->renderException($lastException);

            $content .= "\n" . $this->indent($exceptionContent, 2);
        }

        return $content;
    }

    private function renderName(Step $step): string
    {
        $status = $step->getStatus();

        $icon = IconMap::get($status);
        $content = $step->getName();

        $dataSet = $step->getCurrentDataSet();
        if ($dataSet instanceof DataSetInterface) {
            $content .= ': ' . $dataSet->getName();
        }

        $styledIcon = $status === Status::SUCCESS
            ? $this->consoleOutputFactory->createSuccess($icon)
            : $this->consoleOutputFactory->createFailure($icon);

        $styledContent = $status === Status::SUCCESS
            ? $this->consoleOutputFactory->createSuccess($content)
            : $this->consoleOutputFactory->createFailure($content);

        return $styledIcon . ' ' . $styledContent;
    }

    private function renderCompletedStatements(Step $step): string
    {
        $renderedStatements = [];

        foreach ($step->getCompletedStatementLines() as $completedStatementLine) {
            if (false === $completedStatementLine->getIsDerived()) {
                $renderedStatement = $this->statementLineRenderer->render($completedStatementLine);
                $renderedStatements[] = $this->indent($renderedStatement, 2);
            }
        }

        return implode("\n", $renderedStatements);
    }

    private function renderFailedStatement(
        StatementLine $statementLine,
        string $expectedValue,
        string $actualValue
    ): string {
        $renderedStatement = $this->statementLineRenderer->render($statementLine);
        $statement = $statementLine->getStatement();

        $content = $this->indent($renderedStatement, 2);

        $summary = null;

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

    private function renderException(\Throwable $exception): string
    {
        return '* ' . $this->exceptionRenderer->render($exception);
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
