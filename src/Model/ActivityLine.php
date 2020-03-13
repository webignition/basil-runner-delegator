<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

use webignition\BasilRunner\Model\TerminalString\TerminalString;

class ActivityLine
{
    private const INDENT = '  ';

    private $prefix;
    private $content;

    /**
     * @var ActivityLine|null
     */
    private $parent;

    /**
     * @var ActivityLine[]
     */
    private $children = [];

    public function __construct(TerminalString $prefix, TerminalString $content)
    {
        $this->prefix = $prefix;
        $this->content = $content;
    }

    public function addChild(ActivityLine $child): void
    {
        $this->children[] = $child;
        $child->parent = $this;
    }

    public function __toString(): string
    {
        $indent = str_repeat(self::INDENT, $this->deriveIndentLevel());
        $string = $indent . (string) $this->prefix . ' ' . (string) $this->content;

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
