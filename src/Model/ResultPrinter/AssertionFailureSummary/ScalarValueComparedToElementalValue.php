<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\Comment;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class ScalarValueComparedToElementalValue implements RenderableInterface
{
    private Comment $value;
    private ComparisonOperator $comparisonOperator;
    private ComponentIdentifiedBy $componentIdentifiedBy;

    public function __construct(string $value, string $operator, ElementIdentifierInterface $identifier)
    {
        $this->value = new Comment($value);
        $this->comparisonOperator = new ComparisonOperator($operator);
        $this->componentIdentifiedBy = new ComponentIdentifiedBy($identifier);
    }

    public function render(): string
    {
        return sprintf(
            '* %s %s the value of %s',
            $this->value->render(),
            $this->comparisonOperator->render(),
            $this->componentIdentifiedBy->render()
        );
    }
}
