<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\StatementLine;

use webignition\BasilModels\Action\ResolvedAction;
use webignition\BasilModels\Assertion\ResolvedAssertion;
use webignition\BasilModels\EncapsulatingStatementInterface;
use webignition\BasilModels\StatementInterface;
use webignition\BasilRunner\Model\ResultPrinter\HighlightedFailure;
use webignition\BasilRunner\Model\ResultPrinter\Literal;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\BasilRunner\Model\ResultPrinter\StatusIcon;
use webignition\BasilRunner\Model\TestOutput\StatementLine as OutputStatementLine;
use webignition\BasilRunner\Model\TestOutput\Status;

class StatementLine implements RenderableInterface
{
    private StatementInterface $statement;
    private int $status;
    private StatusIcon $statusIcon;
    private RenderableInterface $statementContent;

    public function __construct(StatementInterface $statement, int $status)
    {
        $this->statement = $statement;
        $this->status = $status;

        $this->statusIcon = new StatusIcon($status);
        $this->statementContent = Status::SUCCESS === $status
            ? new Literal($statement->getSource())
            : new HighlightedFailure($statement->getSource());
    }

    public static function fromOutputStatementLine(OutputStatementLine $statementLine): self
    {
        return new StatementLine(
            $statementLine->getStatement(),
            $statementLine->getHasPassed() ? Status::SUCCESS : Status::FAILURE
        );
    }

    protected function getStatus(): int
    {
        return $this->status;
    }

    public function render(): string
    {
        return sprintf(
            '%s %s',
            $this->statusIcon->render(),
            $this->statementContent->render()
        );

        if ($this->statement instanceof EncapsulatingStatementInterface) {
            $content .= "\n";
            $content .= Status::SUCCESS === $this->status
                ? $this->renderEncapsulatedSource($this->statement)
                : $this->renderEncapsulatedSourceRecursive($this->statement);
        }

        return $content;
    }

    private function renderEncapsulatedSource(EncapsulatingStatementInterface $statement): string
    {
        $label = $statement instanceof ResolvedAction || $statement instanceof ResolvedAssertion
            ? 'resolved from'
            : 'derived from';

        return (new LabelledStatement($label, $statement->getSourceStatement()))->render();
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
