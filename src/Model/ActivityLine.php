<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

use webignition\BasilRunner\Model\TerminalString\Style;
use webignition\BasilRunner\Model\TerminalString\TerminalString;

class ActivityLine
{
    private const INDENT = '  ';

    private $prefix;
    private $prefixStyle;
    private $content;
    private $contentStyle;

    /**
     * @var ActivityLine|null
     */
    private $parent;

    /**
     * @var ActivityLine[]
     */
    private $children = [];

    public function __construct(
        string $prefix,
        Style $prefixStyle,
        string $content,
        Style $contentStyle
    ) {
        $this->prefix = $prefix;
        $this->prefixStyle = $prefixStyle;
        $this->content = $content;
        $this->contentStyle = $contentStyle;
    }

    public function addChild(ActivityLine $child): void
    {
        $this->children[] = $child;
        $child->parent = $this;
    }

    public function __toString(): string
    {
        $indent = str_repeat(self::INDENT, $this->deriveIndentLevel());
        $prefixContent = new TerminalString($this->prefix, $this->prefixStyle);
        $contentContent = new TerminalString($this->content, $this->contentStyle);

        $string = $indent . (string) $prefixContent . ' ' . (string) $contentContent;

        foreach ($this->children as $child) {
            $string .= "\n" . (string) $child;
        }

        return $string;
    }

    private function deriveIndentLevel(): int
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
