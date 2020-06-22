<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\Exception;

use webignition\BasilRunner\Model\ResultPrinter\Comment;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\SymfonyDomCrawlerNavigator\Exception\InvalidLocatorException;

class InvalidLocator implements RenderableInterface
{
    private string $locatorType;
    private Comment $locator;

    public function __construct(InvalidLocatorException $exception)
    {
        $identifier = $exception->getElementIdentifier();

        $this->locatorType = $identifier->isCssSelector() ? 'CSS selector' : 'XPath expression';
        $this->locator = new Comment($identifier->getLocator());
    }

    public function render(): string
    {
        return sprintf(
            '%s %s is not valid',
            $this->locatorType,
            $this->locator->render()
        );
    }
}
