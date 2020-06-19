<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter;

class Literal implements RenderableInterface
{
    use IndentTrait;

    private string $content;
    private int $indentDepth;

    public function __construct(string $content, int $indentDepth = 0)
    {
        $this->content = $content;
        $this->indentDepth = $indentDepth;
    }

    public function render(): string
    {
        return $this->createIndentContent($this->indentDepth) . $this->content;
    }
}
