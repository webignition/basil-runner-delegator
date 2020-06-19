<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\Renderer;

use webignition\BasilRunner\Model\ResultPrinter\Exception\InvalidLocator;
use webignition\SymfonyDomCrawlerNavigator\Exception\InvalidLocatorException;

class ExceptionRenderer
{
    public function render(\Throwable $exception): string
    {
        if ($exception instanceof InvalidLocatorException) {
            return (new InvalidLocator($exception))->render();
        }

        return sprintf(
            'An unknown exception has occurred:' . "\n" .
            '    - %s' . "\n" .
            '    - %s',
            get_class($exception),
            $exception->getMessage()
        );
    }
}
