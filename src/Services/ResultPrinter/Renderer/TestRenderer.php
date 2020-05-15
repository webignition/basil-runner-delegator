<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\Renderer;

use webignition\BasilRunner\Model\TestOutput\Test;
use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;

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
