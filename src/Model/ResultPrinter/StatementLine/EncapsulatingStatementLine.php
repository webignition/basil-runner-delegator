<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\StatementLine;

use webignition\BasilModels\Action\ResolvedAction;
use webignition\BasilModels\Assertion\ResolvedAssertion;
use webignition\BasilModels\EncapsulatingStatementInterface;
use webignition\BasilRunner\Model\ResultPrinter\IndentTrait;
use webignition\BasilRunner\Model\TestOutput\Status;

class EncapsulatingStatementLine extends StatementLine
{
    use IndentTrait;

    private EncapsulatingStatementInterface $statement;

    public function __construct(EncapsulatingStatementInterface $statement, int $status)
    {
        parent::__construct($statement, $status);

        $this->statement = $statement;
    }

    public function render(): string
    {
        $renderedStatement = parent::render();
        $renderedEncapsulatedSource = Status::SUCCESS === $this->getStatus()
            ? $this->renderEncapsulatedSource($this->statement)
            : $this->renderEncapsulatedSourceRecursive($this->statement);

        return $renderedStatement . "\n" . $renderedEncapsulatedSource;
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
