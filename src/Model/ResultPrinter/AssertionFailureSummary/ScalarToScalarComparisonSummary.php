<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\Comment;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;

class ScalarToScalarComparisonSummary implements RenderableInterface
{
    private const IS_OUTCOME = 'is not equal to';
    private const IS_NOT_OUTCOME = 'is equal to';
    private const INCLUDES_OUTCOME = 'does not include';
    private const EXCLUDES_OUTCOME = 'does not exclude';
    private const MATCHES_OUTCOME = 'does not match regular expression';

    private const OPERATOR_OUTCOME_MAP = [
        'is' => self::IS_OUTCOME,
        'is-not' => self::IS_NOT_OUTCOME,
        'includes' => self::INCLUDES_OUTCOME,
        'excludes' => self::EXCLUDES_OUTCOME,
        'matches' => self::MATCHES_OUTCOME,
    ];

    private string $operator;
    private Comment $expectedValue;
    private Comment $actualValue;

    public function __construct(string $operator, string $expectedValue, string $actualValue)
    {
        $this->operator = $operator;
        $this->expectedValue = new Comment($expectedValue);
        $this->actualValue = new Comment($actualValue);
    }

    public function render(): string
    {
        return sprintf(
            "* %s %s %s",
            $this->actualValue->render(),
            self::OPERATOR_OUTCOME_MAP[$this->operator] ?? '',
            $this->expectedValue->render()
        );
    }
}
