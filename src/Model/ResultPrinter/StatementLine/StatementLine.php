<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\StatementLine;

use webignition\BasilModels\StatementInterface;
use webignition\BasilRunner\Model\ResultPrinter\HighlightedFailure;
use webignition\BasilRunner\Model\ResultPrinter\IndentTrait;
use webignition\BasilRunner\Model\ResultPrinter\Literal;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\BasilRunner\Model\ResultPrinter\StatusIcon;
use webignition\BasilRunner\Model\TestOutput\Status;

class StatementLine implements RenderableInterface
{
    use IndentTrait;

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

    protected function getStatus(): int
    {
        return $this->status;
    }

    public function render(): string
    {
        return sprintf(
            '%s%s %s',
            $this->createIndentContent(2),
            $this->statusIcon->render(),
            $this->statementContent->render()
        );
    }
}
