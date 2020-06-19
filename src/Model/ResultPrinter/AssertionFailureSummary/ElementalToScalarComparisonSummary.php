<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\Literal;
use webignition\BasilRunner\Model\ResultPrinter\RenderableCollection;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class ElementalToScalarComparisonSummary extends RenderableCollection
{
    public function __construct(
        ElementIdentifierInterface $identifier,
        string $operator,
        string $expectedValue,
        string $actualValue
    ) {
        $ancestorHierarchy = null === $identifier->getParentIdentifier()
            ? null
            : new AncestorHierarchy($identifier);

        parent::__construct([
            new ComponentIdentifiedBy($identifier),
            new IdentifierProperties($identifier),
            $ancestorHierarchy,
            new WithValueComparedToValue($actualValue, $expectedValue, $operator, 1),
            new Literal(''),
            new ScalarToScalarComparisonSummary($operator, $expectedValue, $actualValue)
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
