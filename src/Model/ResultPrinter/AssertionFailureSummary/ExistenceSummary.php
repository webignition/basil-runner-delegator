<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\Literal;
use webignition\BasilRunner\Model\ResultPrinter\RenderableCollection;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class ExistenceSummary extends RenderableCollection
{
    public function __construct(ElementIdentifierInterface $identifier, string $comparison)
    {
        parent::__construct([
            new ComponentIdentifiedBy($identifier),
            new IdentifierProperties($identifier),
            $this->createAncestorHierarchy($identifier),
            new Literal(('exists' === $comparison ? 'does not exist' : 'does exist'), 1)
        ]);
    }

    public function render(): string
    {
        $content = parent::render();

        $content = ucfirst($content);
        $content = '* ' . $content;

        return $content;
    }

    private function createAncestorHierarchy(ElementIdentifierInterface $identifier): ?RenderableInterface
    {
        $parent = $identifier->getParentIdentifier();
        if (null === $parent) {
            return null;
        }

        $items = [];

        while ($parent instanceof ElementIdentifierInterface) {
            $items[] = new WithParent(1);
            $items[] = new IdentifierProperties($parent);

            $parent = $parent->getParentIdentifier();
        }

        return new RenderableCollection($items);
    }
}
