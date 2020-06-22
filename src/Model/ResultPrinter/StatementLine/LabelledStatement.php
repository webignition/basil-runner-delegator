<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\StatementLine;

use webignition\BasilModels\StatementInterface;
use webignition\BasilRunner\Model\ResultPrinter\Comment;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;

class LabelledStatement implements RenderableInterface
{
    private Comment $label;
    private StatementInterface $statement;

    public function __construct(string $label, StatementInterface $statement)
    {
        $this->label = new Comment('> ' . $label . ':');
        $this->statement = $statement;
    }

    public function render(): string
    {
        return sprintf(
            '%s %s',
            $this->label->render(),
            $this->statement->getSource()
        );
    }
}
