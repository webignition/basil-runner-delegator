<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter;

class Formatter
{
    private const BOLD_START = "\033[1m";
    private const BOLD_END = "\033[0m";
    private const FG_RED_START = "\033[31m";
    private const FG_GREEN_START = "\033[32m";
    private const FG_RESET = "\33[0m";
    public const COLOUR_FG_RED = 'red';
    public const COLOUR_FG_GREEN = 'green';

    /**
     * @var array<string, string>
     */
    private $colours = [
        self::COLOUR_FG_RED => self::FG_RED_START,
        self::COLOUR_FG_GREEN => self::FG_GREEN_START,
    ];

    public static function create(): Formatter
    {
        return new Formatter();
    }

    public function makeBold(string $content): string
    {
        return sprintf(self::BOLD_START . '%s' . self::BOLD_END, $content);
    }

    public function colourise(string $content, string $colour): string
    {
        $start = ($this->colours[$colour] ?? '');
        $end = $start === '' ? '' : self::FG_RESET;

        return $start . $content . $end;
    }
}
