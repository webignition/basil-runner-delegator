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
    ): string {
        $identifierExpansion = $this->createElementIdentifiedByWithExpansion($identifier);
        $outcome = self::COMPARISON_OUTCOME_MAP[$comparison] ?? '';

        return sprintf(
            "%s\n  %s",
            $identifierExpansion,
            $outcome
        );
    }

    public function createForElementalToScalarComparisonAssertion(
        ElementIdentifierInterface $identifier,
        string $comparison,
        string $expectedValue,
        string $actualValue
    ): string {
        $identifierExpansion = $this->createElementIdentifiedByWithExpansion($identifier);

        $outcome = sprintf(
            self::COMPARISON_OUTCOME_MAP[$comparison] ?? '',
            'expected value'
        );

        $expectedValueActualValueLines = $this->createExpectedValueActualValueLines($expectedValue, $actualValue);

        return sprintf(
            "%s\n  %s\n%s",
            $identifierExpansion,
            $outcome,
            $expectedValueActualValueLines
        );
    }

    public function createForElementalToElementalComparisonAssertion(
        ElementIdentifierInterface $identifier,
        ElementIdentifierInterface $valueIdentifier,
        string $comparison,
        string $expectedValue,
        string $actualValue
    ): string {
        $identifierExpansion = $this->createElementIdentifiedByWithExpansion($identifier);

        $outcome = sprintf(
            self::COMPARISON_OUTCOME_MAP[$comparison] ?? '',
            $this->createElementIdentifiedByString($valueIdentifier)
        );

        $valueExpansion = $this->createIdentifierExpansion($valueIdentifier);

        $expectedValueActualValueLines = $this->createExpectedValueActualValueLines($expectedValue, $actualValue);

        return sprintf(
            "%s\n  %s\n%s\n\n%s",
            $identifierExpansion,
            $outcome,
            $valueExpansion,
            $expectedValueActualValueLines
        );
    }

    public function createForScalarToScalarComparisonAssertion(
        string $identifier,
        string $comparison,
        string $expectedValue,
        string $actualValue
    ): string {
        $outcome = sprintf(
            self::COMPARISON_OUTCOME_MAP[$comparison] ?? '',
            'expected value'
        );

        return sprintf(
            "* %s %s\n%s",
            $identifier,
            $outcome,
            $expectedValueActualValueLines = $this->createExpectedValueActualValueLines($expectedValue, $actualValue)
        );
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

    private function createExpectedValueActualValueLines(string $expected, string $actual): string
    {
        return sprintf(
            "%s\n%s",
            $this->createExpectedValueKeyValueLine($expected),
            $this->createActualValueKeyValueLine($actual)
        );
    }

    private function createExpectedValueKeyValueLine(string $expectedValue): string
    {
        return $this->createValueKeyValueLine('expected', $expectedValue);
    }

    private function createActualValueKeyValueLine(string $actualValue): string
    {
        return $this->createValueKeyValueLine('actual', $actualValue, '  ');
    }

    private function createValueKeyValueLine(string $key, string $value, string $padding = ''): string
    {
        return '  - ' . $key . ': ' . $padding . $this->consoleOutputFactory->createComment($value);
    }
}
