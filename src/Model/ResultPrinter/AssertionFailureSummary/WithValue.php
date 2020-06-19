<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\Comment;
use webignition\BasilRunner\Model\ResultPrinter\IndentTrait;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;

class WithValue implements RenderableInterface
{
    use IndentTrait;

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

    private Comment $actualValue;
    private Comment $expectedValue;
    private string $operator;
    private int $indentDepth;

    public function __construct(string $actualValue, string $expectedValue, string $operator, int $indentDepth = 0)
    {
        $this->actualValue = new Comment($actualValue);
        $this->expectedValue = new Comment($expectedValue);
        $this->operator = $operator;
        $this->indentDepth = $indentDepth;
    }

    public function render(): string
    {
        return sprintf(
            '%swith value %s %s %s',
            $this->createIndentContent($this->indentDepth),
            $this->actualValue->render(),
            self::OPERATOR_OUTCOME_MAP[$this->operator] ?? '',
            $this->expectedValue->render()
        );
    }
}
