<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\IndentedContent;
use webignition\BasilRunner\Model\ResultPrinter\RenderableCollection;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class AncestorHierarchy extends RenderableCollection
{
    public function __construct(ElementIdentifierInterface $identifier)
    {
        $items = [];

        $parent = $identifier->getParentIdentifier();

        while ($parent instanceof ElementIdentifierInterface) {
            $items[] = new WithParent();
            $items[] = new IndentedContent(new IdentifierProperties($parent));

            $parent = $parent->getParentIdentifier();
        }

        parent::__construct($items);
    }
}
