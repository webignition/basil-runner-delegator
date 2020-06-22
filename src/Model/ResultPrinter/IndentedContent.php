<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter;

class IndentedContent implements RenderableInterface
{
    private RenderableInterface $content;

    public function __construct(RenderableInterface $content)
    {
        $this->content = $content;
    }

    public function render(): string
    {
        $renderedContent = $this->content->render();

        $renderedContentLines = explode("\n", $renderedContent);

        array_walk($renderedContentLines, function (&$line) {
            if ('' !== $line) {
                $line = '  ' . $line;
            }
        });

        return implode("\n", $renderedContentLines);
    }
}
