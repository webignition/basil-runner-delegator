<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter;

class RenderableCollection implements RenderableInterface
{
    use IndentTrait;

    /**
     * @var RenderableInterface[]
     */
    private array $items;

    private int $indentDepth;

    /**
     * @param array<mixed> $items
     * @param int $indentDepth
     */
    public function __construct(array $items, int $indentDepth = 0)
    {
        $this->items = array_filter($items, function ($item) {
            return $item instanceof RenderableInterface;
        });

        $this->indentDepth = $indentDepth;
    }

    public function render(): string
    {
        $indentContent = $this->createIndentContent($this->indentDepth);

        $renderedItems = [];

        foreach ($this->items as $item) {
            $renderedItems[] = $indentContent . $item->render();
        }

        return implode("\n", $renderedItems);
    }
}
