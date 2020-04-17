<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\FailedAssertion;

use webignition\BasilDomIdentifierFactory\Factory as DomIdentifierFactory;
use webignition\BasilModels\Assertion\AssertionInterface;
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

        $summaryActivityLine = new SummaryLine('');

        if ($identifier instanceof ElementIdentifierInterface) {
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
        } else {
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
