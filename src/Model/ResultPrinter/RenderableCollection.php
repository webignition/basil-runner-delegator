<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter;

class RenderableCollection implements RenderableInterface
{
    /**
     * @var RenderableInterface[]
     */
    private array $items;

    /**
     * @param array<mixed> $items
     */
    public function __construct(array $items)
    {
        $this->items = array_filter($items, function ($item) {
            return $item instanceof RenderableInterface;
        });
    }

    public function render(): string
    {
        $renderedItems = [];
        foreach ($this->items as $item) {
            $renderedItems[] = $item->render();
        }

        return implode("\n", $renderedItems);
    }
}
