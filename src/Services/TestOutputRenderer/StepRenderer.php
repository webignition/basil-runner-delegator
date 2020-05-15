<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\TestOutputRenderer;

use PHPUnit\Runner\BaseTestRunner;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilRunner\Model\TestOutput\IconMap;
use webignition\BasilRunner\Model\TestOutput\StatementLine;
use webignition\BasilRunner\Model\TestOutput\Step;
use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;
use webignition\BasilRunner\Services\ResultPrinter\FailedAssertion\SummaryHandler;
use webignition\BasilRunner\Services\ResultPrinter\StatementLineRenderer;

class StepRenderer
{
    private const INDENT = '  ';

    private $consoleOutputFactory;
    private $statementLineRenderer;
    private $summaryHandler;

    public function __construct(
        ConsoleOutputFactory $consoleOutputFactory,
        StatementLineRenderer $statementLineRenderer,
        SummaryHandler $summaryHandler
    ) {
        $this->consoleOutputFactory = $consoleOutputFactory;
        $this->statementLineRenderer = $statementLineRenderer;
        $this->summaryHandler = $summaryHandler;
    }

    public function render(Step $step): string
    {
        $content = $this->indent($this->renderName($step)) . "\n";
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

        return $content;
    }

    private function renderName(Step $step): string
    {
        $status = $step->getStatus();

        $icon = IconMap::get($status);
        $content = $step->getName();

        $styledIcon = $status === BaseTestRunner::STATUS_PASSED
            ? $this->consoleOutputFactory->createSuccess($icon)
            : $this->consoleOutputFactory->createFailure($icon);

        $styledContent = $status === BaseTestRunner::STATUS_PASSED
            ? $this->consoleOutputFactory->createSuccess($content)
            : $this->consoleOutputFactory->createFailure($content);

        return $styledIcon . ' ' . $styledContent;
    }

    private function renderCompletedStatements(Step $step): string
    {
        $renderedStatements = [];

        foreach ($step->getCompletedStatementLines() as $completedStatementLine) {
            $renderedStatement = $this->statementLineRenderer->render($completedStatementLine);
            $renderedStatements[] = $this->indent($renderedStatement, 2);
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
