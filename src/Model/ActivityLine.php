<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

use webignition\BasilRunner\Model\TerminalString\Style;
use webignition\BasilRunner\Model\TerminalString\TerminalString;

class ActivityLine
{
    private const INDENT = '  ';

    private $icon;
    private $iconStyle;
    private $content;
    private $contentStyle;
    private $parent;

    public function __construct(
        string $icon,
        Style $iconStyle,
        string $content,
        Style $contentStyle,
        ?ActivityLine $parent = null
    ) {
        $this->icon = $icon;
        $this->iconStyle = $iconStyle;
        $this->content = $content;
        $this->contentStyle = $contentStyle;
        $this->parent = $parent;
    }

    public function __toString(): string
    {
        $indent = str_repeat(self::INDENT, $this->deriveIndentLevel());
        $iconContent = new TerminalString($this->icon, $this->iconStyle);
        $contentContent = new TerminalString($this->content, $this->contentStyle);

        return $indent . (string) $iconContent . ' ' . (string) $contentContent;
    }

    private function deriveIndentLevel()
    {
        $indentLevel = 1;
        $parent = $this->parent;

        while ($parent instanceof ActivityLine) {
            $indentLevel++;
            $parent = $parent->parent;
        }

        return $indentLevel;
    }
}
