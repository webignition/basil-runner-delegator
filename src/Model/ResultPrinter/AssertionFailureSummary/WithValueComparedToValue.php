<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\Comment;
use webignition\BasilRunner\Model\ResultPrinter\IndentTrait;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;

class WithValueComparedToValue implements RenderableInterface
{
    use IndentTrait;

    private WithValue $withValue;
    private Comment $expectedValue;
    private ComparisonOperator $comparisonOperator;
    private int $indentDepth;

    public function __construct(string $actualValue, string $expectedValue, string $operator, int $indentDepth = 0)
    {
        $this->withValue = new WithValue($actualValue);
        $this->expectedValue = new Comment($expectedValue);
        $this->comparisonOperator = new ComparisonOperator($operator);
        $this->indentDepth = $indentDepth;
    }

    public function render(): string
    {
        return sprintf(
            '%s%s %s %s',
            $this->createIndentContent($this->indentDepth),
            $this->withValue->render(),
            $this->comparisonOperator->render(),
            $this->expectedValue->render()
        );
    }
}
