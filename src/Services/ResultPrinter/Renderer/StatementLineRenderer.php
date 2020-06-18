<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\Renderer;

use PHPUnit\Runner\BaseTestRunner;
use webignition\BasilModels\Action\ResolvedAction;
use webignition\BasilModels\Assertion\ResolvedAssertion;
use webignition\BasilModels\EncapsulatingStatementInterface;
use webignition\BasilModels\StatementInterface;
use webignition\BasilRunner\Model\TestOutput\IconMap;
use webignition\BasilRunner\Model\TestOutput\StatementLine;
use webignition\BasilRunner\Model\TestOutput\Status;
use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;

class StatementLineRenderer
{
    private ConsoleOutputFactory $consoleOutputFactory;

    public function __construct(ConsoleOutputFactory $consoleOutputFactory)
    {
        $this->consoleOutputFactory = $consoleOutputFactory;
    }

    public function render(StatementLine $statementLine): string
    {
        $statement = $statementLine->getStatement();

        if ($statementLine->getHasPassed()) {
            return $this->renderedPassedStatement($statement);
        }

        return $this->renderedFailedStatement($statement);
    }

    private function renderedPassedStatement(StatementInterface $statement): string
    {
        $content = $this->consoleOutputFactory->createSuccess(IconMap::get(Status::SUCCESS)) .
            ' ' .
            $statement->getSource();

        if ($statement instanceof EncapsulatingStatementInterface) {
            $content .= "\n" . $this->renderEncapsulatedSource($statement);
        }

        return $content;
    }

    private function renderedFailedStatement(StatementInterface $statement): string
    {
        $content = $this->consoleOutputFactory->createFailure(IconMap::get(Status::FAILURE)) .
            ' ' .
            $this->consoleOutputFactory->createHighlightedFailure($statement->getSource());

        if ($statement instanceof EncapsulatingStatementInterface) {
            $content .= "\n" . $this->renderEncapsulatedSourceRecursive($statement);
        }

        return $content;
    }

    private function renderEncapsulatedSource(EncapsulatingStatementInterface $statement): string
    {
        $label = $statement instanceof ResolvedAction || $statement instanceof ResolvedAssertion
            ? 'resolved from'
            : 'derived from';

        return
            '  ' .
            $this->consoleOutputFactory->createComment('> ' . $label . ':') .
            ' ' .
            $statement->getSourceStatement()->getSource();
    }

    private function renderEncapsulatedSourceRecursive(EncapsulatingStatementInterface $statement): string
    {
        $content = $this->renderEncapsulatedSource($statement);

        $sourceStatement = $statement->getSourceStatement();
        if ($sourceStatement instanceof ResolvedAction || $sourceStatement instanceof ResolvedAssertion) {
            $content .= "\n" . $this->renderEncapsulatedSourceRecursive($sourceStatement);
        }

        return $content;
    }
}
