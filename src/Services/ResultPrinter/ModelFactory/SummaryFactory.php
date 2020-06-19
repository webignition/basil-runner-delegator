<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\ModelFactory;

use webignition\BasilDomIdentifierFactory\Factory as DomIdentifierFactory;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ElementalIsRegExpSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ElementalToElementalComparisonSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ElementalToScalarComparisonSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ExistenceSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ScalarIsRegExpSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ScalarToElementalComparisonSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ScalarToScalarComparisonSummary;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class SummaryFactory
{
    private DomIdentifierFactory $domIdentifierFactory;

    public function __construct(DomIdentifierFactory $domIdentifierFactory)
    {
        $this->domIdentifierFactory = $domIdentifierFactory;
    }

    public static function createFactory(): self
    {
        return new SummaryFactory(
            DomIdentifierFactory::createFactory()
        );
    }

    public function create(
        AssertionInterface $assertion,
        string $expectedValue,
        string $actualValue
    ): ?RenderableInterface {
        $identifierString = $assertion->getIdentifier();

        $operator = $assertion->getOperator();
        $identifier = $this->domIdentifierFactory->createFromIdentifierString($identifierString);
        $valueIdentifier = null;

        if ($assertion->isComparison()) {
            $valueString = (string) $assertion->getValue();
            $valueIdentifier = $this->domIdentifierFactory->createFromIdentifierString($valueString);
        }

        $handledOperators = ['is', 'is-not', 'includes', 'excludes', 'matches'];

        if (
            $identifier instanceof ElementIdentifierInterface &&
            $valueIdentifier instanceof ElementIdentifierInterface
        ) {
            if (in_array($operator, $handledOperators)) {
                return new ElementalToElementalComparisonSummary(
                    $identifier,
                    $valueIdentifier,
                    $operator,
                    $expectedValue,
                    $actualValue
                );
            }
        }

        if (null === $identifier && $valueIdentifier instanceof ElementIdentifierInterface) {
            if (in_array($operator, $handledOperators)) {
                return new ScalarToElementalComparisonSummary(
                    $valueIdentifier,
                    $operator,
                    $expectedValue,
                    $actualValue
                );
            }
        }

        if ($identifier instanceof ElementIdentifierInterface && null === $valueIdentifier) {
            if (in_array($operator, ['exists', 'not-exists'])) {
                return new ExistenceSummary($identifier, $operator);
            }

            if (in_array($operator, ['is-regexp'])) {
                return new ElementalIsRegExpSummary($identifier, $actualValue);
            }

            if (in_array($operator, $handledOperators)) {
                return new ElementalToScalarComparisonSummary($identifier, $operator, $expectedValue, $actualValue);
            }
        }

        if (null === $identifier && null === $valueIdentifier) {
            if (in_array($operator, ['is-regexp'])) {
                return new ScalarIsRegExpSummary($actualValue);
            }

            if (in_array($operator, $handledOperators)) {
                return new ScalarToScalarComparisonSummary($operator, $expectedValue, $actualValue);
            }
        }

        return null;
    }
}
