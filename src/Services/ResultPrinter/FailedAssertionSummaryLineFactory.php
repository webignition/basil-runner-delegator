<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter;

use webignition\BasilIdentifierAnalyser\IdentifierTypeAnalyser;
use webignition\BasilRunner\Model\ActivityLine;
use webignition\BasilRunner\Model\KeyValueLine;
use webignition\BasilRunner\Model\SummaryLine;
use webignition\BasilRunner\Model\TerminalString\Style;
use webignition\BasilRunner\Model\TerminalString\TerminalString;
use webignition\DomElementIdentifier\AttributeIdentifierInterface;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class FailedAssertionSummaryLineFactory
{
    private const EXISTS_FINAL_LINE = 'does not exist';
    private const NOT_EXISTS_FINAL_LINE = 'does exist';

    private const COMPARISON_FINAL_LINE_MAP = [
        'exists' => self::EXISTS_FINAL_LINE,
        'not-exists' => self::NOT_EXISTS_FINAL_LINE,
    ];

    /**
     * @var IdentifierTypeAnalyser
     */
    private $identifierTypeAnalyser;
    private $detailStyle;

    public function __construct()
    {
        $this->identifierTypeAnalyser = IdentifierTypeAnalyser::create();
        $this->detailStyle = new Style([
            Style::FOREGROUND_COLOUR => Style::COLOUR_YELLOW,
        ]);
    }

    public function create(ElementIdentifierInterface $identifier, string $comparison): SummaryLine
    {
        $summaryLine = new SummaryLine(
            new TerminalString(sprintf(
                '%s %s identified by:',
                $identifier instanceof AttributeIdentifierInterface ? 'Attribute' : 'Element',
                new TerminalString((string) $identifier, $this->detailStyle)
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

        $finalLine = self::COMPARISON_FINAL_LINE_MAP[$comparison] ?? '';

        $summaryLine->addChild(
            (new ActivityLine(
                new TerminalString(' '),
                new TerminalString($finalLine)
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
                (string) new TerminalString($identifier->getLocator(), $this->detailStyle)
            )
        );

        if ($identifier instanceof AttributeIdentifierInterface) {
            $summaryLine->addChild(
                new KeyValueLine(
                    'attribute name',
                    (string) new TerminalString($identifier->getAttributeName(), $this->detailStyle)
                )
            );
        }

        $summaryLine->addChild(
            new KeyValueLine(
                'ordinal position',
                (string) new TerminalString((string) ($identifier->getOrdinalPosition() ?? 1), $this->detailStyle)
            )
        );

        return $summaryLine;
    }
}
