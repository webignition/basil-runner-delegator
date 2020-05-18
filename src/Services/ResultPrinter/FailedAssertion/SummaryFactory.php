<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\FailedAssertion;

use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;
use webignition\DomElementIdentifier\AttributeIdentifierInterface;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class SummaryFactory
{
    private const EXISTS_OUTCOME = 'does not exist';
    private const NOT_EXISTS_OUTCOME = 'does exist';
    private const IS_OUTCOME = 'is not equal to';
    private const IS_NOT_OUTCOME = 'is equal to';
    private const INCLUDES_OUTCOME = 'does not include';
    private const EXCLUDES_OUTCOME = 'does not exclude';
    private const MATCHES_OUTCOME = 'does not match regular expression';

    private const COMPARISON_OUTCOME_MAP = [
        'exists' => self::EXISTS_OUTCOME,
        'not-exists' => self::NOT_EXISTS_OUTCOME,
        'is' => self::IS_OUTCOME,
        'is-not' => self::IS_NOT_OUTCOME,
        'includes' => self::INCLUDES_OUTCOME,
        'excludes' => self::EXCLUDES_OUTCOME,
        'matches' => self::MATCHES_OUTCOME,
    ];

    private $consoleOutputFactory;

    public function __construct(ConsoleOutputFactory $consoleOutputFactory)
    {
        $this->consoleOutputFactory = $consoleOutputFactory;
    }

    public function createForElementalExistenceAssertion(
        ElementIdentifierInterface $identifier,
        string $comparison
    ): string {
        $identifierExpansion = $this->createElementIdentifiedByWithExpansion($identifier);
        $outcome = self::COMPARISON_OUTCOME_MAP[$comparison] ?? '';

        return $identifierExpansion . "\n" . '  ' . $outcome;
    }

    public function createForElementalToScalarComparisonAssertion(
        ElementIdentifierInterface $identifier,
        string $comparison,
        string $expectedValue,
        string $actualValue
    ): string {
        $identifierExpansion = $this->createElementIdentifiedByWithExpansion($identifier);

        $summary = sprintf(
            "%s\n  %s %s %s",
            $identifierExpansion,
            $this->createWithValuePortion($actualValue),
            self::COMPARISON_OUTCOME_MAP[$comparison] ?? '',
            $this->consoleOutputFactory->createComment($expectedValue)
        );

        return $this->appendScalarToScalarSummary($summary, $comparison, $expectedValue, $actualValue);
    }

    public function createForElementalToElementalComparisonAssertion(
        ElementIdentifierInterface $identifier,
        ElementIdentifierInterface $valueIdentifier,
        string $comparison,
        string $expectedValue,
        string $actualValue
    ): string {
        $identifierExpansion = $this->createElementIdentifiedByWithExpansion($identifier);

        $valueExpansion = $this->createIdentifierExpansion($valueIdentifier);

        $outcome = self::COMPARISON_OUTCOME_MAP[$comparison] ?? '';
        $outcomeSuffix = $comparison === 'matches'
            ? 'within the value of'
            : 'the value of';

        $summary = sprintf(
            "%s\n  %s %s %s\n%s\n  %s",
            $identifierExpansion,
            $this->createWithValuePortion($actualValue),
            $outcome . ' ' . $outcomeSuffix,
            $this->createElementIdentifiedByString($valueIdentifier),
            $valueExpansion,
            $this->createWithValuePortion($expectedValue)
        );

        return $this->appendScalarToScalarSummary($summary, $comparison, $expectedValue, $actualValue);
    }

    public function createForScalarToScalarComparisonAssertion(
        string $comparison,
        string $expectedValue,
        string $actualValue
    ): string {
        return sprintf(
            "* %s %s %s",
            $this->consoleOutputFactory->createComment($actualValue),
            self::COMPARISON_OUTCOME_MAP[$comparison] ?? '',
            $this->consoleOutputFactory->createComment($expectedValue)
        );
    }

    public function createForScalarToElementalComparisonAssertion(
        ElementIdentifierInterface $valueIdentifier,
        string $comparison,
        string $expectedValue,
        string $actualValue
    ): string {
        $valueExpansion = $this->createIdentifierExpansion($valueIdentifier);

        $outcome = self::COMPARISON_OUTCOME_MAP[$comparison] ?? '';
        $outcomeSuffix = $comparison === 'matches'
            ? 'within the value of'
            : 'the value of';

        $summary = sprintf(
            "* %s %s %s\n%s\n  %s",
            $this->consoleOutputFactory->createComment($actualValue),
            $outcome . ' ' . $outcomeSuffix,
            $this->createElementIdentifiedByString($valueIdentifier),
            $valueExpansion,
            $this->createWithValuePortion($expectedValue)
        );

        return $this->appendScalarToScalarSummary($summary, $comparison, $expectedValue, $actualValue);
    }

    private function createWithValuePortion(string $value): string
    {
        return 'with value ' . $this->consoleOutputFactory->createComment($value);
    }

    private function appendScalarToScalarSummary(
        string $summary,
        string $comparison,
        string $expectedValue,
        string $actualValue
    ): string {
        $scalarToScalarSummary = $this->createForScalarToScalarComparisonAssertion(
            $comparison,
            $expectedValue,
            $actualValue
        );

        return $summary . "\n\n" . $scalarToScalarSummary;
    }

    private function createElementIdentifiedByWithExpansion(ElementIdentifierInterface $identifier): string
    {
        return sprintf(
            "* %s\n%s",
            ucfirst($this->createElementIdentifiedByString($identifier)),
            $this->createIdentifierExpansion($identifier)
        );
    }

    private function createIdentifierExpansion(ElementIdentifierInterface $identifier): string
    {
        $identifierExpansion = '';

        $identifierLines = $this->createIdentifierPropertiesSummaryLines($identifier);

        foreach ($identifierLines as $identifierPropertySummaryLine) {
            $identifierExpansion .= '  ' . $identifierPropertySummaryLine . "\n";
        }

        $parent = $identifier->getParentIdentifier();

        while ($parent instanceof ElementIdentifierInterface) {
            $identifierExpansion .= '  with parent:' . "\n";

            $identifierLines = $this->createIdentifierPropertiesSummaryLines($parent);
            foreach ($identifierLines as $identifierPropertySummaryLine) {
                $identifierExpansion .= '  ' . $identifierPropertySummaryLine . "\n";
            }

            $parent = $parent->getParentIdentifier();
        }

        return rtrim($identifierExpansion);
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
     * @return string[]
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

    private function createValueKeyValueLine(string $key, string $value, string $padding = ''): string
    {
        return '  - ' . $key . ': ' . $padding . $this->consoleOutputFactory->createComment($value);
    }
}
