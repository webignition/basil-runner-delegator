<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\Renderer;

use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;
use webignition\SymfonyDomCrawlerNavigator\Exception\InvalidLocatorException;

class ExceptionRenderer
{
    private ConsoleOutputFactory $consoleOutputFactory;

    public function __construct(ConsoleOutputFactory $consoleOutputFactory)
    {
        $this->consoleOutputFactory = $consoleOutputFactory;
    }

    public function render(\Throwable $exception): string
    {
        if ($exception instanceof InvalidLocatorException) {
            return $this->renderInvalidLocatorException($exception);
        }

        return sprintf(
            'An unknown exception has occurred:' . "\n" .
            '    - %s' . "\n" .
            '    - %s',
            get_class($exception),
            $exception->getMessage()
        );
    }

    private function renderInvalidLocatorException(InvalidLocatorException $exception): string
    {
        $identifier = $exception->getElementIdentifier();

        return sprintf(
            '%s %s is not valid',
            $identifier->isCssSelector() ? 'CSS selector' : 'XPath expression',
            $this->consoleOutputFactory->createComment($identifier->getLocator())
        );
    }
}
