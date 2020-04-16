<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter;

use webignition\BasilRunner\Model\ActivityLine;
use webignition\BasilRunner\Model\KeyValueLine;
use webignition\BasilRunner\Model\SummaryLine;
use webignition\DomElementIdentifier\AttributeIdentifierInterface;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class FailedAssertionSummaryLineFactory
{
    private const IS_FINAL_LINE = 'is not equal to expected value';

    private const COMPARISON_FINAL_LINE_MAP = [
        'is' => self::IS_FINAL_LINE,
    ];

    private $consoleOutputFactory;

    public function __construct(ConsoleOutputFactory $consoleOutputFactory)
    {
        $this->consoleOutputFactory = $consoleOutputFactory;
    }

    public function createForExistenceAssertion(ElementIdentifierInterface $identifier, string $comparison): SummaryLine
    {
        $finalLine = 'exists' === $comparison
            ? 'does not exist'
            : 'does exist';

        return $this->create($identifier, new ActivityLine(' ', $finalLine));
    }

    public function createForComparisonAssertion(
        ElementIdentifierInterface $identifier,
        string $comparison,
        ?string $expectedValue,
        ?string $actualValue
    ): SummaryLine {
        $finalLine = self::COMPARISON_FINAL_LINE_MAP[$comparison] ?? '';

        $finalActivityLine = new ActivityLine(' ', $finalLine);

        $finalActivityLine->addChild(
            (new KeyValueLine(
                'expected',
                $this->consoleOutputFactory->createComment((string) $expectedValue)
            ))->decreaseIndent()
        );

        $finalActivityLine->addChild(
            (new KeyValueLine(
                'actual',
                '  ' . $this->consoleOutputFactory->createComment((string) $actualValue)
            ))->decreaseIndent()
        );

        return $this->create($identifier, $finalActivityLine);
    }

    private function create(ElementIdentifierInterface $identifier, ActivityLine $finalActivityLine): SummaryLine
    {
        $summaryLine = new SummaryLine(sprintf(
            '%s %s identified by:',
            $identifier instanceof AttributeIdentifierInterface ? 'Attribute' : 'Element',
            $this->consoleOutputFactory->createComment((string) $identifier)
        ));

        $this->applyIdentifierPropertiesSummaryLines($summaryLine, $identifier);

        $parent = $identifier->getParentIdentifier();

        while ($parent instanceof ElementIdentifierInterface) {
            $summaryLine->addChild(
                (new ActivityLine(
                    ' ',
                    'with parent:'
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
                $this->consoleOutputFactory->createComment($identifier->getLocator())
            )
        );

        if ($identifier instanceof AttributeIdentifierInterface) {
            $summaryLine->addChild(
                new KeyValueLine(
                    'attribute name',
                    $this->consoleOutputFactory->createComment($identifier->getAttributeName())
                )
            );
        }

        $summaryLine->addChild(
            new KeyValueLine(
                'ordinal position',
                $this->consoleOutputFactory->createComment((string) ($identifier->getOrdinalPosition() ?? 1))
            )
        );

        return $summaryLine;
    }
}
