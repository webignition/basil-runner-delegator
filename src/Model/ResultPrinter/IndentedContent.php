<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter;

class IndentedContent implements RenderableInterface
{
    private RenderableInterface $content;
    private int $indentDepth;

    public function __construct(RenderableInterface $content, int $indentDepth = 1)
    {
        $this->content = $content;
        $this->indentDepth = $indentDepth;
    }

    public function render(): string
    {
        $renderedContent = $this->content->render();

        $renderedContentLines = explode("\n", $renderedContent);
        $indentContent = str_repeat('  ', $this->indentDepth);

        array_walk($renderedContentLines, function (&$line) use ($indentContent) {
            if ('' !== $line) {
                $line = $indentContent . $line;
            }
        });

        return implode("\n", $renderedContentLines);
    }
}
