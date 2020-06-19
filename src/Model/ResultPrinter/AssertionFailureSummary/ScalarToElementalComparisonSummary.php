<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\Literal;
use webignition\BasilRunner\Model\ResultPrinter\RenderableCollection;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class ScalarToElementalComparisonSummary extends RenderableCollection
{
    public function __construct(
        ElementIdentifierInterface $valueIdentifier,
        string $operator,
        string $expectedValue,
        string $actualValue
    ) {
        $valueAncestorHierarchy = null === $valueIdentifier->getParentIdentifier()
            ? null
            : new AncestorHierarchy($valueIdentifier);

        parent::__construct([
            new ScalarValueComparedToElementalValue($actualValue, $operator, $valueIdentifier),
            new IdentifierProperties($valueIdentifier),
            $valueAncestorHierarchy,
            new WithValue($expectedValue, 1),
            new Literal(''),
            new ScalarToScalarComparisonSummary($operator, $expectedValue, $actualValue)
        ]);
    }
}
