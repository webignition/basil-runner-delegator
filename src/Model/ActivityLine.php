<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model;

class ActivityLine
{
    private const INDENT = '  ';

    private $prefix;
    private $content;

    /**
     * @var int
     */
    private $indentOffset = 0;

    /**
     * @var ActivityLine|null
     */
    private $parent;

    /**
     * @var ActivityLine[]
     */
    private $children = [];

    public function __construct(string $prefix, string $content)
    {
        $this->prefix = $prefix;
        $this->content = $content;
    }

    public function decreaseIndent(): self
    {
        $this->indentOffset--;

        return $this;
    }

    public function increaseIndent(): self
    {
        $this->indentOffset++;

        return $this;
    }

    public function addChild(ActivityLine $child): void
    {
        $this->children[] = $child;
        $child->parent = $this;
    }

    public function __toString(): string
    {
        $indent = str_repeat(self::INDENT, $this->deriveIndentLevel());
        $string = $indent . $this->prefix . ' ' . $this->content;

        foreach ($this->children as $child) {
            $string .= "\n" . $child;
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

        $indentLevel += $this->indentOffset;

        if ($indentLevel < 1) {
            $indentLevel = 1;
        }

        return $indentLevel;
    }
}
