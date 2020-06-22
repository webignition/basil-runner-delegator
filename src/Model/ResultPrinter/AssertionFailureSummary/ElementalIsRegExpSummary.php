<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\IndentedContent;
use webignition\BasilRunner\Model\ResultPrinter\Literal;
use webignition\BasilRunner\Model\ResultPrinter\RenderableCollection;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class ElementalIsRegExpSummary extends RenderableCollection
{
    public function __construct(ElementIdentifierInterface $identifier, string $regexp)
    {
        $ancestorHierarchy = null === $identifier->getParentIdentifier()
            ? null
            : new IndentedContent(new AncestorHierarchy($identifier));

        parent::__construct([
            new ComponentIdentifiedBy($identifier),
            new IndentedContent(new IdentifierProperties($identifier), 2),
            $ancestorHierarchy,
            new IndentedContent(new Literal('is not a valid regular expression')),
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
