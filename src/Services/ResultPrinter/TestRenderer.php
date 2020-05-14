<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter;

use webignition\BasilRunner\Model\TestOutput\Test;

class TestRenderer
{
    private $consoleOutputFactory;

    public function __construct(ConsoleOutputFactory $consoleOutputFactory)
    {
        $this->consoleOutputFactory = $consoleOutputFactory;
    }

    public function render(Test $test): string
    {
        return $this->consoleOutputFactory->createTestPath($test->getRelativePath());
    }
}
