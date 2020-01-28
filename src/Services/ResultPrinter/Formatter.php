<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter;

class Formatter
{
    private const BOLD_START = "\033[1m";
    private const BOLD_END = "\033[0m";

    public static function create(): Formatter
    {
        return new Formatter();
    }

    public function makeBold(string $content): string
    {
        return sprintf(self::BOLD_START . '%s' . self::BOLD_END, $content);
    }
}
