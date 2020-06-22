<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\StatementLine;

use webignition\BasilModels\StatementInterface;
use webignition\BasilRunner\Model\ResultPrinter\HighlightedFailure;
use webignition\BasilRunner\Model\ResultPrinter\Literal;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\BasilRunner\Model\ResultPrinter\StatusIcon;
use webignition\BasilRunner\Model\TestOutput\Status;

class Header implements RenderableInterface
{
    private StatusIcon $statusIcon;
    private RenderableInterface $source;

    public function __construct(StatementInterface $statement, int $status)
    {
        $this->statusIcon = new StatusIcon($status);
        $this->source = Status::SUCCESS === $status
            ? new Literal($statement->getSource())
            : new HighlightedFailure($statement->getSource());
    }

    public function render(): string
    {
        return sprintf(
            '%s %s',
            $this->statusIcon->render(),
            $this->source->render()
        );
    }
}
