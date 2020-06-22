<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\ModelFactory;

use webignition\BasilRunner\Model\ResultPrinter\Exception\InvalidLocator;
use webignition\BasilRunner\Model\ResultPrinter\Exception\Unknown;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\SymfonyDomCrawlerNavigator\Exception\InvalidLocatorException;

class ExceptionFactory
{
    public function create(\Throwable $exception): RenderableInterface
    {
        if ($exception instanceof InvalidLocatorException) {
            return new InvalidLocator($exception);
        }

        return new Unknown($exception);
    }
}
