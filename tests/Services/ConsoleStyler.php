<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Services;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyleInterface;

class ConsoleStyler
{
    public function bold(string $path): string
    {
        return $this->applyStyle($path, new OutputFormatterStyle(null, null, ['bold']));
    }

    public function success(string $text): string
    {
        return $this->applyStyle($text, new OutputFormatterStyle('green'));
    }

    public function failure(string $text): string
    {
        return $this->applyStyle($text, new OutputFormatterStyle('red'));
    }

    public function highlightedFailure(string $text): string
    {
        return $this->applyStyle($text, new OutputFormatterStyle('white', 'red'));
    }

    public function comment(string $text): string
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
