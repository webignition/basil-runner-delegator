<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter\FailedAssertion;

use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ElementalIsRegExpSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ElementalToElementalComparisonSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ElementalToScalarComparisonSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ExistenceSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ScalarIsRegExpSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ScalarToElementalComparisonSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ScalarToScalarComparisonSummary;
use webignition\BasilRunner\Services\ResultPrinter\FailedAssertion\SummaryFactory;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;
use webignition\DomElementIdentifier\ElementIdentifier;

class SummaryFactoryTest extends AbstractBaseTest
{
    private SummaryFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new SummaryFactory();
    }

    public function testCreateForElementalExistenceAssertion()
    {
        $elementIdentifier = new ElementIdentifier('.selector');
        $operator = 'exists';
        $expectedModel = new ExistenceSummary($elementIdentifier, $operator);

        $this->assertEquals(
            $expectedModel,
            $this->factory->createForElementalExistenceAssertion($elementIdentifier, $operator)
        );
    }

    public function testCreateForElementalToScalarComparisonAssertion()
    {
        $elementIdentifier = new ElementIdentifier('.selector');
        $operator = 'is';
        $expectedValue = 'expected';
        $actualValue = 'actual';

        $expectedModel = new ElementalToScalarComparisonSummary(
            $elementIdentifier,
            $operator,
            $expectedValue,
            $actualValue
        );

        $this->assertEquals(
            $expectedModel,
            $this->factory->createForElementalToScalarComparisonAssertion(
                $elementIdentifier,
                $operator,
                $expectedValue,
                $actualValue
            )
        );
    }

    public function testCreateScalarToScalarComparisonAssertion()
    {
        $operator = 'is';
        $expectedValue = 'expected';
        $actualValue = 'actual';

        $expectedModel = new ScalarToScalarComparisonSummary($operator, $expectedValue, $actualValue);

        $this->assertEquals(
            $expectedModel,
            $this->factory->createForScalarToScalarComparisonAssertion(
                $operator,
                $expectedValue,
                $actualValue
            )
        );
    }

    public function testCreateForElementalToElementalComparisonAssertion()
    {
        $identifier = new ElementIdentifier('.identifier');
        $valueIdentifier = new ElementIdentifier('.value');
        $operator = 'is';
        $expectedValue = 'expected';
        $actualValue = 'actual';

        $expectedModel = new ElementalToElementalComparisonSummary(
            $identifier,
            $valueIdentifier,
            $operator,
            $expectedValue,
            $actualValue
        );

        $this->assertEquals(
            $expectedModel,
            $this->factory->createForElementalToElementalComparisonAssertion(
                $identifier,
                $valueIdentifier,
                $operator,
                $expectedValue,
                $actualValue
            )
        );
    }

    public function testCreateForScalarToElementalComparison()
    {
        $valueIdentifier = new ElementIdentifier('.value');
        $operator = 'is';
        $expectedValue = 'expected';
        $actualValue = 'actual';

        $expectedModel = new ScalarToElementalComparisonSummary(
            $valueIdentifier,
            $operator,
            $expectedValue,
            $actualValue
        );

        $this->assertEquals(
            $expectedModel,
            $this->factory->createForScalarToElementalComparisonAssertion(
                $valueIdentifier,
                $operator,
                $expectedValue,
                $actualValue
            )
        );
    }

    public function testCreateForElementalIsRegExpAssertion()
    {
        $identifier = new ElementIdentifier('.selector');
        $regexp = 'invalid-regex';

        $expectedModel = new ElementalIsRegExpSummary($identifier, $regexp);

        $this->assertEquals(
            $expectedModel,
            $this->factory->createForElementalIsRegExpAssertion(
                $identifier,
                $regexp
            )
        );
    }

    public function testCreateForScalarIsRegExpAssertion()
    {
        $regexp = 'invalid-regex';

        $expectedModel = new ScalarIsRegExpSummary($regexp);

        $this->assertEquals(
            $expectedModel,
            $this->factory->createForScalarIsRegExpAssertion($regexp)
        );
    }
}
