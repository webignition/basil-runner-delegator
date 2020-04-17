<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter;

use webignition\BasilDomIdentifierFactory\Factory as DomIdentifierFactory;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilRunner\Model\SummaryLine;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class FailedAssertionSummaryHandler
{
    /**
     * @var AssertionSummaryLineFactory
     */
    private $assertionSummaryLineFactory;

    /**
     * @var DomIdentifierFactory
     */
    private $domIdentifierFactory;

    public function __construct(
        DomIdentifierFactory $domIdentifierFactory,
        AssertionSummaryLineFactory $assertionSummaryLineFactory
    ) {
        $this->assertionSummaryLineFactory = $assertionSummaryLineFactory;
        $this->domIdentifierFactory = $domIdentifierFactory;
    }

    public function handle(AssertionInterface $assertion, string $expectedValue, string $actualValue): SummaryLine
    {
        $identifierString = $assertion->getIdentifier();

        $comparison = $assertion->getComparison();
        $identifier = $this->domIdentifierFactory->createFromIdentifierString($identifierString);

        $summaryActivityLine = new SummaryLine('');

        if ($identifier instanceof ElementIdentifierInterface) {
            if (in_array($comparison, ['exists', 'not-exists'])) {
                $summaryActivityLine = $this->assertionSummaryLineFactory->createForElementalExistenceAssertion(
                    $identifier,
                    $comparison
                );
            }

            if (in_array($comparison, ['is'])) {
                $summaryActivityLine =
                    $this->assertionSummaryLineFactory->createForElementalToScalarComparisonAssertion(
                        $identifier,
                        $comparison,
                        $expectedValue,
                        $actualValue
                    );
            }
        } else {
            if (in_array($comparison, ['is'])) {
                $summaryActivityLine =
                    $this->assertionSummaryLineFactory->createForScalarToScalarComparisonAssertion(
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
