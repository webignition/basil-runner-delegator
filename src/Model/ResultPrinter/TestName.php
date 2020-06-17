<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter;

class TestName implements RenderableInterface
{
    public const START = '<test-name>';
    public const END = '</test-name>';

    private string $testName;

    public function __construct(string $testName)
    {
        $this->testName = $testName;
    }

    public function render(): string
    {
        return sprintf(
            self::START . '%s' . self::END,
            $this->testName
        );
    }
}
