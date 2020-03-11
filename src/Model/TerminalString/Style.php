<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\TerminalString;

class Style
{
    public const FOREGROUND_COLOUR = 'foreground';
    public const BACKGROUND_COLOUR = 'background';
    public const DECORATIONS = 'decorations';

    private const BOLD = "\033[1m";

    private const FG_RED = "\033[31m";
    private const FG_GREEN = "\033[32m";
    private const FG_WHITE = "\033[37m";

    private const BG_BLACK = "\033[40m";
    private const BG_RED = "\033[41m";

    private const RESET = "\033[0m";

    public const COLOUR_RED = 'red';
    public const COLOUR_GREEN = 'green';
    public const COLOUR_WHITE = 'white';
    public const COLOUR_BLACK = 'black';
    public const DECORATION_BOLD = 'bold';

    /**
     * @var array<string, string>
     */
    private $foregroundColours = [
        self::COLOUR_RED => self::FG_RED,
        self::COLOUR_GREEN => self::FG_GREEN,
        self::COLOUR_WHITE => self::FG_WHITE,
    ];

    /**
     * @var array<string, string>
     */
    private $backgroundColours = [
        self::COLOUR_BLACK => self::BG_BLACK,
        self::COLOUR_RED => self::BG_RED,
    ];

    /**
     * @var array<string, string>
     */
    private $decorations = [
        self::DECORATION_BOLD => self::BOLD,
    ];

    /**
     * @var string|null
     */
    private $foregroundCode;

    /**
     * @var string|null
     */
    private $backgroundCode;

    /**
     * @var string[]
     */
    private $decorationCodes = [];

    /**
     * @param array<mixed> $style
     */
    public function __construct($style = [])
    {
        $foregroundColour = $style[self::FOREGROUND_COLOUR] ?? null;
        $backgroundColour = $style[self::BACKGROUND_COLOUR] ?? null;
        $decorations = $style[self::DECORATIONS] ?? [];

        $this->foregroundCode = $this->foregroundColours[$foregroundColour] ?? null;
        $this->backgroundCode = $this->backgroundColours[$backgroundColour] ?? null;

        foreach ($decorations as $decoration) {
            $this->addDecoration($decoration);
        }
    }

    public function __toString(): string
    {
        $foreground = (string) $this->foregroundCode;
        $background = (string) $this->backgroundCode;
        $decoration = implode('', $this->decorationCodes);

        $includeReset = '' !== $foreground || '' !== $background || '' !== $decoration;

        return sprintf(
            '%s%s%s%%s%s',
            $foreground,
            $background,
            $decoration,
            $includeReset ? self::RESET : ''
        );
    }

    private function addDecoration(string $decoration): void
    {
        $decorationCode = $this->decorations[$decoration] ?? null;

        if (is_string($decorationCode)) {
            $this->decorationCodes[] = $decorationCode;
            $this->decorationCodes = array_unique($this->decorationCodes);
        }
    }
}
