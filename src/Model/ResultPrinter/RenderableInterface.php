<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter;

interface RenderableInterface
{
    public function render(): string;
}
