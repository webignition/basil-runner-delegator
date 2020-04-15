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
    private const IS_FINAL_LINE = 'is not equal to expected value';

    private const COMPARISON_FINAL_LINE_MAP = [
        'is' => self::IS_FINAL_LINE,
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

    public function createForExistenceAssertion(ElementIdentifierInterface $identifier, string $comparison): SummaryLine
    {
        $finalLine = 'exists' === $comparison
            ? 'does not exist'
            : 'does exist';

        return $this->create($identifier, new ActivityLine(
            new TerminalString(' '),
            new TerminalString($finalLine)
        ));
    }

    public function createForComparisonAssertion(
        ElementIdentifierInterface $identifier,
        string $comparison,
        ?string $expectedValue,
        ?string $actualValue
    ): SummaryLine {
        $finalLine = self::COMPARISON_FINAL_LINE_MAP[$comparison] ?? '';

        $finalActivityLine = (new ActivityLine(
            new TerminalString(' '),
            new TerminalString($finalLine)
        ))->decreaseIndent();

        $finalActivityLine
            ->addChild(
                new KeyValueLine(
                    'expected',
                    (string) new TerminalString((string) $expectedValue, $this->detailStyle)
                )
            );

        $finalActivityLine
            ->addChild(
                new KeyValueLine(
                    'actual',
                    '  ' . (string) new TerminalString((string) $actualValue, $this->detailStyle)
                )
            );

        return $this->create($identifier, $finalActivityLine);
    }

    private function create(ElementIdentifierInterface $identifier, ActivityLine $finalActivityLine): SummaryLine
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

        $finalActivityLine = $finalActivityLine->decreaseIndent();

        $summaryLine->addChild($finalActivityLine);

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
