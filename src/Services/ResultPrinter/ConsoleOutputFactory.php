<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyleInterface;

class ConsoleOutputFactory
{
    public function createComment(string $text): string
    {
        return $this->applyStyle($text, new OutputFormatterStyle('yellow'));
    }

    private function applyStyle(string $text, OutputFormatterStyleInterface $style): string
    {
        if ('' === $text) {
            return $text;
        }

        return $style->apply($text);
    }
}
