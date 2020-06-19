<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\IndentTrait;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class WithValueElemental implements RenderableInterface
{
    use IndentTrait;

    private WithValue $withValue;
    private ComponentIdentifiedBy $expectedIdentifiedBy;
    private ComparisonOperator $comparisonOperator;
    private int $indentDepth;

    public function __construct(
        string $actualValue,
        ElementIdentifierInterface $valueIdentifier,
        string $operator,
        int $indentDepth = 0
    ) {
        $this->withValue = new WithValue($actualValue);
        $this->expectedIdentifiedBy = new ComponentIdentifiedBy($valueIdentifier);
        $this->comparisonOperator = new ComparisonOperator($operator);
        $this->indentDepth = $indentDepth;
    }

    public function render(): string
    {
        return sprintf(
            '%s%s %s the value of %s',
            $this->createIndentContent($this->indentDepth),
            $this->withValue->render(),
            $this->comparisonOperator->render(),
            $this->expectedIdentifiedBy->render()
        );
    }
}
