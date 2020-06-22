<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\RenderableCollection;
use webignition\DomElementIdentifier\AttributeIdentifierInterface;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class IdentifierProperties extends RenderableCollection
{
    public function __construct(ElementIdentifierInterface $identifier)
    {
        $properties = [];

        $properties[] = new Property(
            $identifier->isCssSelector() ? 'CSS selector' : 'XPath expression',
            $identifier->getLocator()
        );

        if ($identifier instanceof AttributeIdentifierInterface) {
            $properties[] = new Property('attribute name', $identifier->getAttributeName());
        }

        $properties[] = new Property('ordinal position', (string) ($identifier->getOrdinalPosition() ?? 1));

        parent::__construct($properties);
    }
}
