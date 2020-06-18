<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter;

trait IndentTrait
{
    private function createIndentContent(int $depth): string
    {
        return str_repeat('  ', $depth);
    }
}
