<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\FailedAssertion;

use webignition\BasilRunner\Model\ActivityLine;
use webignition\BasilRunner\Model\KeyValueLine;
use webignition\BasilRunner\Model\SummaryLine;
use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;
use webignition\DomElementIdentifier\AttributeIdentifierInterface;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class SummaryLineFactory
{
    private const EXISTS_OUTCOME = 'does not exist';
    private const NOT_EXISTS_OUTCOME = 'does exist';
    private const IS_OUTCOME = 'is not equal to %s';

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
        string $expectedValue,
        string $actualValue
    ): SummaryLine {
        $outcome = sprintf(
            self::COMPARISON_OUTCOME_MAP[$comparison] ?? '',
            'expected value'
        );

        $finalActivityLine = new ActivityLine(' ', $outcome);

        $finalActivityLine->addChild($this->createExpectedValueKeyValueLine($expectedValue));
        $finalActivityLine->addChild($this->createActualValueKeyValueLine($actualValue));

        return $this->createForElementalAssertion($identifier, $finalActivityLine);
    }

    public function createForElementalToElementalComparisonAssertion(
        ElementIdentifierInterface $identifier,
        ElementIdentifierInterface $valueIdentifier,
        string $comparison,
        string $expectedValue,
        string $actualValue
    ): SummaryLine {
        $outcome = sprintf(
            self::COMPARISON_OUTCOME_MAP[$comparison] ?? '',
            $this->createElementIdentifiedByString($valueIdentifier)
        );

        $finalActivityLine = new ActivityLine(' ', $outcome);

        $identifierLines = $this->createIdentifierPropertiesSummaryLines($valueIdentifier);
        foreach ($identifierLines as $identifierPropertySummaryLine) {
            $finalActivityLine->addChild($identifierPropertySummaryLine);
        }

        $finalActivityLine->addChild($this->createExpectedValueKeyValueLine($expectedValue));
        $finalActivityLine->addChild($this->createActualValueKeyValueLine($actualValue));

        return $this->createForElementalAssertion($identifier, $finalActivityLine);
    }

    public function createForScalarToScalarComparisonAssertion(
        string $identifier,
        string $comparison,
        string $expectedValue,
        string $actualValue
    ): SummaryLine {
        $outcome = sprintf(
            self::COMPARISON_OUTCOME_MAP[$comparison] ?? '',
            'expected value'
        );

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
        $summaryLine = new SummaryLine(ucfirst($this->createElementIdentifiedByString($identifier)));

        $identifierLines = $this->createIdentifierPropertiesSummaryLines($identifier);
        foreach ($identifierLines as $identifierPropertySummaryLine) {
            $summaryLine->addChild($identifierPropertySummaryLine->increaseIndent());
        }

        $parent = $identifier->getParentIdentifier();

        while ($parent instanceof ElementIdentifierInterface) {
            $summaryLine->addChild(
                (new ActivityLine(
                    ' ',
                    'with parent:'
                ))->decreaseIndent()
            );

            $identifierLines = $this->createIdentifierPropertiesSummaryLines($parent);
            foreach ($identifierLines as $identifierPropertySummaryLine) {
                $summaryLine->addChild($identifierPropertySummaryLine->increaseIndent());
            }

            $parent = $parent->getParentIdentifier();
        }

        $finalActivityLine = $finalActivityLine->decreaseIndent();

        $summaryLine->addChild($finalActivityLine);

        return $summaryLine;
    }

    private function createElementIdentifiedByString(ElementIdentifierInterface $identifier): string
    {
        return sprintf(
            '%s %s identified by:',
            $identifier instanceof AttributeIdentifierInterface ? 'attribute' : 'element',
            $this->consoleOutputFactory->createComment((string) $identifier)
        );
    }

    /**
     * @param ElementIdentifierInterface $identifier
     *
     * @return ActivityLine[]
     */
    private function createIdentifierPropertiesSummaryLines(ElementIdentifierInterface $identifier): array
    {
        $summaryLines = [
            $this->createValueKeyValueLine(
                $identifier->isCssSelector() ? 'CSS selector' : 'XPath expression',
                $identifier->getLocator()
            )
        ];

        if ($identifier instanceof AttributeIdentifierInterface) {
            $summaryLines[] = $this->createValueKeyValueLine(
                'attribute name',
                $identifier->getAttributeName()
            );
        }

        $summaryLines[] = $this->createValueKeyValueLine(
            'ordinal position',
            (string) ($identifier->getOrdinalPosition() ?? 1)
        );

        return $summaryLines;
    }

    private function createExpectedValueKeyValueLine(string $expectedValue): ActivityLine
    {
        return ($this->createValueKeyValueLine('expected', $expectedValue))->decreaseIndent();
    }

    private function createActualValueKeyValueLine(string $actualValue): ActivityLine
    {
        return ($this->createValueKeyValueLine('actual', $actualValue, '  '))->decreaseIndent();
    }

    private function createValueKeyValueLine(string $key, string $value, string $padding = ''): KeyValueLine
    {
        return new KeyValueLine(
            $key,
            $padding . $this->consoleOutputFactory->createComment($value)
        );
    }
}
