<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\Literal;
use webignition\BasilRunner\Model\ResultPrinter\RenderableCollection;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class ElementalIsRegExpSummary extends RenderableCollection
{
    public function __construct(ElementIdentifierInterface $identifier, string $regexp)
    {
        $ancestorHierarchy = null === $identifier->getParentIdentifier()
            ? null
            : new AncestorHierarchy($identifier);

        parent::__construct([
            new ComponentIdentifiedBy($identifier),
            new IdentifierProperties($identifier),
            $ancestorHierarchy,
            new Literal('is not a valid regular expression', 1),
            new Literal(''),
            new ScalarIsRegExpSummary($regexp)
        ]);
    }

    public function render(): string
    {
        $content = parent::render();
        $content = '* The value of ' . $content;

        return $content;
    }
}
