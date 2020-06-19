<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Services\ResultPrinter\FailedAssertion;

use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ElementalToElementalComparisonSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ElementalToScalarComparisonSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ExistenceSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ElementalIsRegExpSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ScalarIsRegExpSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ScalarToElementalComparisonSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ScalarToScalarComparisonSummary;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class SummaryFactory
{
    public function createForElementalExistenceAssertion(
        ElementIdentifierInterface $identifier,
        string $comparison
    ): string {
        return (new ExistenceSummary($identifier, $comparison))->render();
    }

    public function createForElementalIsRegExpAssertion(ElementIdentifierInterface $identifier, string $regexp): string
    {
        return (new ElementalIsRegExpSummary($identifier, $regexp))->render();
    }

    public function createForScalarIsRegExpAssertion(string $regexp): string
    {
        return (new ScalarIsRegExpSummary($regexp))->render();
    }

    public function createForElementalToScalarComparisonAssertion(
        ElementIdentifierInterface $identifier,
        string $comparison,
        string $expectedValue,
        string $actualValue
    ): string {
        return
            (new ElementalToScalarComparisonSummary($identifier, $comparison, $expectedValue, $actualValue))->render();
    }

    public function createForElementalToElementalComparisonAssertion(
        ElementIdentifierInterface $identifier,
        ElementIdentifierInterface $valueIdentifier,
        string $comparison,
        string $expectedValue,
        string $actualValue
    ): string {
        $summary = new ElementalToElementalComparisonSummary(
            $identifier,
            $valueIdentifier,
            $comparison,
            $expectedValue,
            $actualValue
        );

        return $summary->render();
    }

    public function createForScalarToScalarComparisonAssertion(
        string $comparison,
        string $expectedValue,
        string $actualValue
    ): string {
        return (new ScalarToScalarComparisonSummary($comparison, $expectedValue, $actualValue))->render();
    }

    public function createForScalarToElementalComparisonAssertion(
        ElementIdentifierInterface $valueIdentifier,
        string $comparison,
        string $expectedValue,
        string $actualValue
    ): string {
        $summary = new ScalarToElementalComparisonSummary(
            $valueIdentifier,
            $comparison,
            $expectedValue,
            $actualValue
        );

        return $summary->render();
    }
}
