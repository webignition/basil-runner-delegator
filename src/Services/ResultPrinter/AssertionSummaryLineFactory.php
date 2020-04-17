<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter;

use webignition\BasilRunner\Model\ActivityLine;
use webignition\BasilRunner\Model\KeyValueLine;
use webignition\BasilRunner\Model\SummaryLine;
use webignition\DomElementIdentifier\AttributeIdentifierInterface;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class AssertionSummaryLineFactory
{
    private const EXISTS_OUTCOME = 'does not exist';
    private const NOT_EXISTS_OUTCOME = 'does exist';
    private const IS_OUTCOME = 'is not equal to expected value';

    private const COMPARISON_OUTCOME_MAP = [
        'exists' => self::EXISTS_OUTCOME,
        'not-exists' => self::NOT_EXISTS_OUTCOME,
        'is' => self::IS_OUTCOME,
    ];

    private $consoleOutputFactory;

    public function __construct(ConsoleOutputFactory $consoleOutputFactory)
    {
        $this->consoleOutputFactory = $consoleOutputFactory;
    }

    public function createForElementalExistenceAssertion(
        ElementIdentifierInterface $identifier,
        string $comparison
    ): SummaryLine {
        $outcome = self::COMPARISON_OUTCOME_MAP[$comparison] ?? '';

        return $this->createForElementalAssertion($identifier, new ActivityLine(' ', $outcome));
    }

    public function createForElementalToScalarComparisonAssertion(
        ElementIdentifierInterface $identifier,
        string $comparison,
        ?string $expectedValue,
        ?string $actualValue
    ): SummaryLine {
        $outcome = self::COMPARISON_OUTCOME_MAP[$comparison] ?? '';

        $finalActivityLine = new ActivityLine(' ', $outcome);

        $finalActivityLine->addChild($this->createExpectedValueKeyValueLine((string) $expectedValue));
        $finalActivityLine->addChild($this->createActualValueKeyValueLine((string) $actualValue));

        return $this->createForElementalAssertion($identifier, $finalActivityLine);
    }

    public function createForScalarToScalarComparisonAssertion(
        string $identifier,
        string $comparison,
        ?string $expectedValue,
        ?string $actualValue
    ): SummaryLine {
        $outcome = self::COMPARISON_OUTCOME_MAP[$comparison] ?? '';

        $summaryLine = new SummaryLine(
            $identifier . ' ' . $outcome
        );

        $summaryLine->addChild($this->createExpectedValueKeyValueLine($expectedValue)->increaseIndent());
        $summaryLine->addChild($this->createActualValueKeyValueLine($actualValue)->increaseIndent());

        return $summaryLine;
    }

    private function createForElementalAssertion(
        ElementIdentifierInterface $identifier,
        ActivityLine $finalActivityLine
    ): SummaryLine {
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
        $summaryLine->addChild($this->createValueKeyValueLine(
            $identifier->isCssSelector() ? 'CSS selector' : 'XPath expression',
            $identifier->getLocator()
        ));

        if ($identifier instanceof AttributeIdentifierInterface) {
            $summaryLine->addChild($this->createValueKeyValueLine(
                'attribute name',
                $identifier->getAttributeName()
            ));
        }

        $summaryLine->addChild($this->createValueKeyValueLine(
            'ordinal position',
            (string) ($identifier->getOrdinalPosition() ?? 1)
        ));

        return $summaryLine;
    }

    private function createExpectedValueKeyValueLine(?string $expectedValue): ActivityLine
    {
        return ($this->createValueKeyValueLine('expected', $expectedValue))->decreaseIndent();
    }

    private function createActualValueKeyValueLine(?string $actualValue): ActivityLine
    {
        return ($this->createValueKeyValueLine('actual', $actualValue, '  '))->decreaseIndent();
    }

    private function createValueKeyValueLine(string $key, ?string $value, string $padding = ''): KeyValueLine
    {
        return new KeyValueLine(
            $key,
            $padding . $this->consoleOutputFactory->createComment((string) $value)
        );
    }
}
