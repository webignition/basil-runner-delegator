<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class WithValueElemental implements RenderableInterface
{
    private WithValue $withValue;
    private ComponentIdentifiedBy $expectedIdentifiedBy;
    private ComparisonOperator $comparisonOperator;

    public function __construct(
        string $actualValue,
        ElementIdentifierInterface $valueIdentifier,
        string $operator
    ) {
        $this->withValue = new WithValue($actualValue);
        $this->expectedIdentifiedBy = new ComponentIdentifiedBy($valueIdentifier);
        $this->comparisonOperator = new ComparisonOperator($operator);
    }

    public function render(): string
    {
        return sprintf(
            '%s %s the value of %s',
            $this->withValue->render(),
            $this->comparisonOperator->render(),
            $this->expectedIdentifiedBy->render()
        );
    }
}
