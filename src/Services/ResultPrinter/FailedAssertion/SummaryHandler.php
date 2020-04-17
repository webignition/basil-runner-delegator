<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\FailedAssertion;

use webignition\BasilDomIdentifierFactory\Factory as DomIdentifierFactory;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilModels\Assertion\ComparisonAssertionInterface;
use webignition\BasilRunner\Model\SummaryLine;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class SummaryHandler
{
    /**
     * @var SummaryLineFactory
     */
    private $summaryLineFactory;

    /**
     * @var DomIdentifierFactory
     */
    private $domIdentifierFactory;

    public function __construct(
        DomIdentifierFactory $domIdentifierFactory,
        SummaryLineFactory $summaryLineFactory
    ) {
        $this->summaryLineFactory = $summaryLineFactory;
        $this->domIdentifierFactory = $domIdentifierFactory;
    }

    public function handle(AssertionInterface $assertion, string $expectedValue, string $actualValue): SummaryLine
    {
        $identifierString = $assertion->getIdentifier();

        $comparison = $assertion->getComparison();
        $identifier = $this->domIdentifierFactory->createFromIdentifierString($identifierString);
        $valueIdentifier = null;

        if ($assertion instanceof ComparisonAssertionInterface) {
            $valueString = $assertion->getValue();
            $valueIdentifier = $this->domIdentifierFactory->createFromIdentifierString($valueString);
        }

        $summaryActivityLine = new SummaryLine('');

        if (
            $identifier instanceof ElementIdentifierInterface &&
            $valueIdentifier instanceof ElementIdentifierInterface
        ) {
            if (in_array($comparison, ['is'])) {
                $summaryActivityLine =
                    $this->summaryLineFactory->createForElementalToElementalComparisonAssertion(
                        $identifier,
                        $valueIdentifier,
                        $comparison,
                        $expectedValue,
                        $actualValue
                    );
            }
        }

//        if (null === $identifier && $valueIdentifier instanceof ElementIdentifierInterface) {
//            // identifier is scalar, value is element
//        }

        if ($identifier instanceof ElementIdentifierInterface && null === $valueIdentifier) {
            if (in_array($comparison, ['exists', 'not-exists'])) {
                $summaryActivityLine = $this->summaryLineFactory->createForElementalExistenceAssertion(
                    $identifier,
                    $comparison
                );
            }

            if (in_array($comparison, ['is'])) {
                $summaryActivityLine =
                    $this->summaryLineFactory->createForElementalToScalarComparisonAssertion(
                        $identifier,
                        $comparison,
                        $expectedValue,
                        $actualValue
                    );
            }
        }

        if (null === $identifier && null === $valueIdentifier) {
            if (in_array($comparison, ['is'])) {
                $summaryActivityLine =
                    $this->summaryLineFactory->createForScalarToScalarComparisonAssertion(
                        $identifierString,
                        $comparison,
                        $expectedValue,
                        $actualValue
                    );
            }
        }

        return $summaryActivityLine;
    }
}
