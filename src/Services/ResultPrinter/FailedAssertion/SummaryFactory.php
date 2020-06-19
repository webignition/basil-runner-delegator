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
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class SummaryFactory
{
    public function createForElementalExistenceAssertion(
        ElementIdentifierInterface $identifier,
        string $operator
    ): RenderableInterface {
        return new ExistenceSummary($identifier, $operator);
    }

    public function createForElementalIsRegExpAssertion(
        ElementIdentifierInterface $identifier,
        string $regexp
    ): RenderableInterface {
        return new ElementalIsRegExpSummary($identifier, $regexp);
    }

    public function createForScalarIsRegExpAssertion(string $regexp): RenderableInterface
    {
        return new ScalarIsRegExpSummary($regexp);
    }

    public function createForElementalToScalarComparisonAssertion(
        ElementIdentifierInterface $identifier,
        string $operator,
        string $expectedValue,
        string $actualValue
    ): RenderableInterface {
        return new ElementalToScalarComparisonSummary($identifier, $operator, $expectedValue, $actualValue);
    }

    public function createForElementalToElementalComparisonAssertion(
        ElementIdentifierInterface $identifier,
        ElementIdentifierInterface $valueIdentifier,
        string $comparison,
        string $expectedValue,
        string $actualValue
    ): RenderableInterface {
        return new ElementalToElementalComparisonSummary(
            $identifier,
            $valueIdentifier,
            $comparison,
            $expectedValue,
            $actualValue
        );
    }

    public function createForScalarToScalarComparisonAssertion(
        string $operator,
        string $expectedValue,
        string $actualValue
    ): RenderableInterface {
        return new ScalarToScalarComparisonSummary($operator, $expectedValue, $actualValue);
    }

    public function createForScalarToElementalComparisonAssertion(
        ElementIdentifierInterface $valueIdentifier,
        string $operator,
        string $expectedValue,
        string $actualValue
    ): RenderableInterface {
        return new ScalarToElementalComparisonSummary(
            $valueIdentifier,
            $operator,
            $expectedValue,
            $actualValue
        );
    }
}
