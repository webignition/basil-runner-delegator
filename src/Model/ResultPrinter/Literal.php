<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter;

class Literal implements RenderableInterface
{
    private string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function render(): string
    {
        return $this->content;
    }
}
