<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\StepRenderer;

use PHPUnit\Runner\BaseTestRunner;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\StatementInterface;
use webignition\BasilRunner\Model\TestOutput\Step;
use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;
use webignition\BasilRunner\Services\ResultPrinter\FailedAssertion\SummaryHandler;

class StepRenderer
{
    private const INDENT = '  ';
    private const DEFAULT_ICON = '?';

    private $consoleOutputFactory;
    private $statementLineFactory;
    private $summaryHandler;

    /**
     * @var array<int, string>
     */
    private $icons = [
        BaseTestRunner::STATUS_PASSED => '✓',
        BaseTestRunner::STATUS_FAILURE => 'x',
    ];

    public function __construct(
        ConsoleOutputFactory $consoleOutputFactory,
        StatementLineFactory $statementLineFactory,
        SummaryHandler $summaryHandler
    ) {
        $this->consoleOutputFactory = $consoleOutputFactory;
        $this->statementLineFactory = $statementLineFactory;
        $this->summaryHandler = $summaryHandler;
    }

    public function render(Step $step): string
    {
        $content = $this->indent($this->renderName($step)) . "\n";
        $content .= $this->renderCompletedStatements($step);

        $failedStatement = $step->getFailedStatement();
        if ($failedStatement instanceof StatementInterface) {
            if (0 !== count($step->getCompletedStatements())) {
                $content .= "\n";
            }

            $content .= $this->renderFailedStatement(
                $failedStatement,
                $step->getExpectedValue(),
                $step->getActualValue()
            );
        }

        return $content;
    }

    private function renderName(Step $step): string
    {
        $status = $step->getStatus();

        $icon = $this->icons[$status] ?? self::DEFAULT_ICON;
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

        foreach ($step->getCompletedStatements() as $statement) {
            $renderedStatement = $this->statementLineFactory->createCompletedLine($statement);
            $renderedStatements[] = $this->indent($renderedStatement, 2);
        }

        return implode("\n", $renderedStatements);
    }

    private function renderFailedStatement(
        StatementInterface $statement,
        string $expectedValue,
        string $actualValue
    ): string {
        $renderedStatement = $this->statementLineFactory->createFailedLine($statement);

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
