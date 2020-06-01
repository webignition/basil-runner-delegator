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
        return $this->renderStatement(
            $statement,
            $this->consoleOutputFactory->createSuccess(IconMap::get(BaseTestRunner::STATUS_PASSED)) .
            ' ' .
            $statement->getSource()
        );
    }

    private function renderedFailedStatement(StatementInterface $statement): string
    {
        return $this->renderStatement(
            $statement,
            $this->consoleOutputFactory->createFailure(IconMap::get(BaseTestRunner::STATUS_FAILURE)) .
            ' ' .
            $this->consoleOutputFactory->createHighlightedFailure($statement->getSource())
        );
    }

    private function renderStatement(StatementInterface $statement, string $renderedStatement): string
    {
        $content = $renderedStatement;

        $renderedEncapsulatedStatement = $this->renderEncapsulatedStatement($statement);
        if (null !== $renderedEncapsulatedStatement) {
            $content .= "\n" . $renderedEncapsulatedStatement;
        }

        return $content;
    }

    private function renderEncapsulatedStatement(StatementInterface $statement): ?string
    {
        if ($statement instanceof EncapsulatingStatementInterface) {
            $label = $statement instanceof ResolvedAction || $statement instanceof ResolvedAssertion
                ? 'resolved from'
                : 'derived from';

            return
                '  ' .
                $this->consoleOutputFactory->createComment('> ' . $label . ':') .
                ' ' .
                $statement->getSourceStatement()->getSource();
        }

        return null;
    }
}
