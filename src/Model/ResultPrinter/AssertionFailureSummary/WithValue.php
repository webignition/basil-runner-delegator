<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\Comment;
use webignition\BasilRunner\Model\ResultPrinter\IndentTrait;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;

class WithValue implements RenderableInterface
{
    use IndentTrait;

    private Comment $value;
    private int $indentDepth;

    public function __construct(string $value, int $indentDepth = 0)
    {
        $this->value = new Comment($value);
        $this->indentDepth = $indentDepth;
    }

    public function render(): string
    {
        return sprintf(
            '%swith value %s',
            $this->createIndentContent($this->indentDepth),
            $this->value->render()
        );
    }
}
