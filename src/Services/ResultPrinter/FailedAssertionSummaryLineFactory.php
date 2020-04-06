<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter;

use webignition\BasilIdentifierAnalyser\IdentifierTypeAnalyser;
use webignition\BasilRunner\Model\ActivityLine;
use webignition\BasilRunner\Model\KeyValueLine;
use webignition\BasilRunner\Model\SummaryLine;
use webignition\BasilRunner\Model\TerminalString\TerminalString;
use webignition\DomElementIdentifier\AttributeIdentifierInterface;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class FailedAssertionSummaryLineFactory
{
    /**
     * @var IdentifierTypeAnalyser
     */
    private $identifierTypeAnalyser;

    public function __construct()
    {
        $this->identifierTypeAnalyser = IdentifierTypeAnalyser::create();
    }

    public function create(ElementIdentifierInterface $identifier): SummaryLine
    {
        $summaryLine = new SummaryLine(
            new TerminalString(sprintf(
                '%s identified by:',
                $identifier instanceof AttributeIdentifierInterface ? 'Attribute' : 'Element'
            ))
        );

        $this->applyIdentifierPropertiesSummaryLines($summaryLine, $identifier);

        $parent = $identifier->getParentIdentifier();

        while ($parent instanceof ElementIdentifierInterface) {
            $summaryLine->addChild(
                (new ActivityLine(
                    new TerminalString(' '),
                    new TerminalString('with parent:')
                ))->decreaseIndent()
            );

            $this->applyIdentifierPropertiesSummaryLines($summaryLine, $parent);

            $parent = $parent->getParentIdentifier();
        }

        $summaryLine->addChild(
            (new ActivityLine(
                new TerminalString(' '),
                new TerminalString('does not exist')
            ))->decreaseIndent()
        );

        return $summaryLine;
    }

    private function applyIdentifierPropertiesSummaryLines(
        SummaryLine $summaryLine,
        ElementIdentifierInterface $identifier
    ): SummaryLine {
        $summaryLine->addChild(
            new KeyValueLine(
                $identifier->isCssSelector() ? 'CSS selector' : 'XPath expression',
                $identifier->getLocator()
            )
        );

        if ($identifier instanceof AttributeIdentifierInterface) {
            $summaryLine->addChild(
                new KeyValueLine(
                    'attribute name',
                    $identifier->getAttributeName()
                )
            );
        }

        $summaryLine->addChild(
            new KeyValueLine(
                'ordinal position',
                (string) ($identifier->getOrdinalPosition() ?? 1)
            )
        );

        return $summaryLine;
    }
}
