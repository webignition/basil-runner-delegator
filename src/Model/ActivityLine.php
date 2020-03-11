<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

use webignition\BasilRunner\Model\TerminalString\Style;
use webignition\BasilRunner\Model\TerminalString\TerminalString;

class ActivityLine
{
    private const INDENT = '  ';

    private $indentLevel;
    private $icon;
    private $iconStyle;
    private $content;
    private $contentStyle;

    public function __construct(
        int $indentLevel,
        string $icon,
        Style $iconStyle,
        string $content,
        Style $contentStyle
    ) {
        $this->indentLevel = $indentLevel;
        $this->icon = $icon;
        $this->iconStyle = $iconStyle;
        $this->content = $content;
        $this->contentStyle = $contentStyle;
    }

    public function __toString(): string
    {
        $indent = str_repeat(self::INDENT, $this->indentLevel);
        $iconContent = new TerminalString($this->icon, $this->iconStyle);
        $contentContent = new TerminalString($this->content, $this->contentStyle);

        return $indent . (string) $iconContent . ' ' . (string) $contentContent;
    }
}
