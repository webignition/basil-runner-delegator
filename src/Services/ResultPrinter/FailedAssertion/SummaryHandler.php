<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\FailedAssertion;

use webignition\BasilDomIdentifierFactory\Factory as DomIdentifierFactory;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class SummaryHandler
{
    private SummaryFactory $summaryFactory;
    private DomIdentifierFactory $domIdentifierFactory;

    public function __construct(
        DomIdentifierFactory $domIdentifierFactory,
        SummaryFactory $summaryLineFactory
    ) {
        $this->summaryFactory = $summaryLineFactory;
        $this->domIdentifierFactory = $domIdentifierFactory;
    }

    public function handle(AssertionInterface $assertion, string $expectedValue, string $actualValue): ?string
    {
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
                return ($this->summaryFactory->createForElementalToElementalComparisonAssertion(
                    $identifier,
                    $valueIdentifier,
                    $operator,
                    $expectedValue,
                    $actualValue
                ))->render();
            }
        }

        if (null === $identifier && $valueIdentifier instanceof ElementIdentifierInterface) {
            if (in_array($operator, $handledOperators)) {
                return ($this->summaryFactory->createForScalarToElementalComparisonAssertion(
                    $valueIdentifier,
                    $operator,
                    $expectedValue,
                    $actualValue
                ))->render();
            }
        }

        if ($identifier instanceof ElementIdentifierInterface && null === $valueIdentifier) {
            if (in_array($operator, ['exists', 'not-exists'])) {
                return ($this->summaryFactory->createForElementalExistenceAssertion(
                    $identifier,
                    $operator
                ))->render();
            }

            if (in_array($operator, ['is-regexp'])) {
                return ($this->summaryFactory->createForElementalIsRegExpAssertion(
                    $identifier,
                    $actualValue
                ))->render();
            }

            if (in_array($operator, $handledOperators)) {
                return ($this->summaryFactory->createForElementalToScalarComparisonAssertion(
                    $identifier,
                    $operator,
                    $expectedValue,
                    $actualValue
                ))->render();
            }
        }

        if (null === $identifier && null === $valueIdentifier) {
            if (in_array($operator, ['is-regexp'])) {
                return ($this->summaryFactory->createForScalarIsRegExpAssertion($actualValue))->render();
            }

            if (in_array($operator, $handledOperators)) {
                return ($this->summaryFactory->createForScalarToScalarComparisonAssertion(
                    $operator,
                    $expectedValue,
                    $actualValue
                ))->render();
            }
        }

        return null;
    }
}
