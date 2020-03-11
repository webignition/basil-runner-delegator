<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\TerminalString;

class TerminalString
{
    private $content;
    private $style;

    public function __construct(string $content, ?Style $style = null)
    {
        $this->content = $content;
        $this->style = $style instanceof Style ? $style : new Style();
    }

    public function __toString(): string
    {
        if ('' === $this->content) {
            return '';
        }

        return sprintf((string) $this->style, $this->content);
    }
}
