<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter;

abstract class AbstractEncapsulatedContentLine implements RenderableInterface
{
    private string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    abstract protected function getRenderTemplate(): string;

    public function render(): string
    {
        return sprintf(
            $this->getRenderTemplate(),
            $this->content,
        );
    }
}
