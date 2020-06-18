<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\Exception;

use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\SymfonyDomCrawlerNavigator\Exception\InvalidLocatorException;

class Unknown implements RenderableInterface
{
    private \Throwable $exception;

    public function __construct(InvalidLocatorException $exception)
    {
        $this->exception = $exception;
    }

    public function render(): string
    {
        return sprintf(
            'An unknown exception has occurred:' . "\n" .
            '    - %s' . "\n" .
            '    - %s',
            get_class($this->exception),
            $this->exception->getMessage()
        );
    }
}
