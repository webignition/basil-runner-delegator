<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\Literal;
use webignition\BasilRunner\Model\ResultPrinter\RenderableCollection;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class ExistenceSummary extends RenderableCollection
{
    public function __construct(ElementIdentifierInterface $identifier, string $operator)
    {
        $ancestorHierarchy = null === $identifier->getParentIdentifier()
            ? null
            : new AncestorHierarchy($identifier);

        parent::__construct([
            new ComponentIdentifiedBy($identifier),
            new IdentifierProperties($identifier),
            $ancestorHierarchy,
            new Literal(('exists' === $operator ? 'does not exist' : 'does exist'), 1)
        ]);
    }

    public function render(): string
    {
        $content = parent::render();

        $content = ucfirst($content);
        $content = '* ' . $content;

        return $content;
    }
}
