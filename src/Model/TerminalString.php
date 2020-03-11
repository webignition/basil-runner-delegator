<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

class TerminalString
{
    public const COLOUR_RED = 'red';
    public const COLOUR_GREEN = 'green';
    public const COLOUR_WHITE = 'white';
    public const COLOUR_BLACK = 'black';
    public const DECORATION_BOLD = 'bold';

    private $content;
    private $format;

    public function __construct(string $content, ?TerminalStringFormat $format = null)
    {
        $this->content = $content;
        $this->format = $format instanceof TerminalStringFormat ? $format : new TerminalStringFormat();
    }

    public function __toString(): string
    {
        if ('' === $this->content) {
            return '';
        }

        return sprintf((string) $this->format, $this->content);
    }
}
