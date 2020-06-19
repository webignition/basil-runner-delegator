<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter\FailedAssertion;

use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilParser\AssertionParser;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ElementalIsRegExpSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ElementalToElementalComparisonSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ElementalToScalarComparisonSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ExistenceSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ScalarIsRegExpSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ScalarToElementalComparisonSummary;
use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ScalarToScalarComparisonSummary;
use webignition\BasilRunner\Model\ResultPrinter\RenderableInterface;
use webignition\BasilRunner\Services\ResultPrinter\FailedAssertion\SummaryHandler;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;
use webignition\DomElementIdentifier\ElementIdentifier;

class SummaryHandlerTest extends AbstractBaseTest
{
    private SummaryHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = SummaryHandler::createHandler();
    }

    /**
     * @dataProvider handleDataProvider
     */
    public function testHandle(
        AssertionInterface $assertion,
        string $expectedValue,
        string $actualValue,
        RenderableInterface $expectedModel
    ) {
        $this->assertEquals(
            $expectedModel,
            $this->handler->handle($assertion, $expectedValue, $actualValue)
        );
    }

    public function handleDataProvider(): array
    {
        $assertionParser = AssertionParser::create();

        return [
            'exists assertion, elemental identifier' => [
                'assertion' => $assertionParser->parse('$".selector" exists'),
                'expectedValue' => '',
                'actualValue' => '',
                'expectedModel' => new ExistenceSummary(new ElementIdentifier('.selector'), 'exists'),
            ],
            'not-exists assertion, elemental identifier' => [
                'assertion' => $assertionParser->parse('$".selector" not-exists'),
                'expectedValue' => '',
                'actualValue' => '',
                'expectedModel' => new ExistenceSummary(new ElementIdentifier('.selector'), 'not-exists'),
            ],
            'is assertion, elemental to scalar comparison' => [
                'assertion' => $assertionParser->parse('$".selector" is "value"'),
                'expectedValue' => 'expected value',
                'actualValue' => 'actual value',
                'expectedModel' => new ElementalToScalarComparisonSummary(
                    new ElementIdentifier('.selector'),
                    'is',
                    'expected value',
                    'actual value'
                ),
            ],
            'is assertion, scalar to scalar comparison' => [
                'assertion' => $assertionParser->parse('$page.title is "Page Title"'),
                'expectedValue' => 'Page Title',
                'actualValue' => 'Different Page Title',
                'expectedModel' => new ScalarToScalarComparisonSummary(
                    'is',
                    'Page Title',
                    'Different Page Title'
                ),
            ],
            'is assertion, scalar to elemental comparison' => [
                'assertion' => $assertionParser->parse('$page.title is $".value"'),
                'expectedValue' => 'Page Title',
                'actualValue' => 'Different Page Title',
                'expectedModel' => new ScalarToElementalComparisonSummary(
                    new ElementIdentifier('.value'),
                    'is',
                    'Page Title',
                    'Different Page Title'
                ),
            ],
            'is assertion, elemental to elemental comparison' => [
                'assertion' => $assertionParser->parse('$".selector" is $".value"'),
                'expectedValue' => 'expected value',
                'actualValue' => 'actual value',
                'expectedModel' => new ElementalToElementalComparisonSummary(
                    new ElementIdentifier('.selector'),
                    new ElementIdentifier('.value'),
                    'is',
                    'expected value',
                    'actual value'
                ),
            ],
            'is-not assertion, elemental to scalar comparison' => [
                'assertion' => $assertionParser->parse('$".selector" is-not "value"'),
                'expectedValue' => 'expected value',
                'actualValue' => 'actual value',
                'expectedModel' => new ElementalToScalarComparisonSummary(
                    new ElementIdentifier('.selector'),
                    'is-not',
                    'expected value',
                    'actual value'
                ),
            ],
            'is-not assertion, scalar to scalar comparison' => [
                'assertion' => $assertionParser->parse('$page.title is-not "Page Title"'),
                'expectedValue' => 'Page Title',
                'actualValue' => 'Different Page Title',
                'expectedModel' => new ScalarToScalarComparisonSummary(
                    'is-not',
                    'Page Title',
                    'Different Page Title'
                ),
            ],
            'is-not assertion, scalar to elemental comparison' => [
                'assertion' => $assertionParser->parse('$page.title is-not $".value"'),
                'expectedValue' => 'Page Title',
                'actualValue' => 'Different Page Title',
                'expectedModel' => new ScalarToElementalComparisonSummary(
                    new ElementIdentifier('.value'),
                    'is-not',
                    'Page Title',
                    'Different Page Title'
                ),
            ],
            'is-not assertion, elemental to elemental comparison' => [
                'assertion' => $assertionParser->parse('$".selector" is-not $".value"'),
                'expectedValue' => 'expected value',
                'actualValue' => 'actual value',
                'expectedModel' => new ElementalToElementalComparisonSummary(
                    new ElementIdentifier('.selector'),
                    new ElementIdentifier('.value'),
                    'is-not',
                    'expected value',
                    'actual value'
                ),
            ],
            'includes assertion, elemental to scalar comparison' => [
                'assertion' => $assertionParser->parse('$".selector" includes "value"'),
                'expectedValue' => 'expected value',
                'actualValue' => 'actual value',
                'expectedModel' => new ElementalToScalarComparisonSummary(
                    new ElementIdentifier('.selector'),
                    'includes',
                    'expected value',
                    'actual value'
                ),
            ],
            'includes assertion, scalar to scalar comparison' => [
                'assertion' => $assertionParser->parse('$page.title includes "Page Title"'),
                'expectedValue' => 'Page Title',
                'actualValue' => 'Different Page Title',
                'expectedModel' => new ScalarToScalarComparisonSummary(
                    'includes',
                    'Page Title',
                    'Different Page Title'
                ),
            ],
            'includes assertion, scalar to elemental comparison' => [
                'assertion' => $assertionParser->parse('$page.title includes $".value"'),
                'expectedValue' => 'Page Title',
                'actualValue' => 'Different Page Title',
                'expectedModel' => new ScalarToElementalComparisonSummary(
                    new ElementIdentifier('.value'),
                    'includes',
                    'Page Title',
                    'Different Page Title'
                ),
            ],
            'includes assertion, elemental to elemental comparison' => [
                'assertion' => $assertionParser->parse('$".selector" includes $".value"'),
                'expectedValue' => 'expected value',
                'actualValue' => 'actual value',
                'expectedModel' => new ElementalToElementalComparisonSummary(
                    new ElementIdentifier('.selector'),
                    new ElementIdentifier('.value'),
                    'includes',
                    'expected value',
                    'actual value'
                ),
            ],
            'excludes assertion, elemental to scalar comparison' => [
                'assertion' => $assertionParser->parse('$".selector" excludes "value"'),
                'expectedValue' => 'expected value',
                'actualValue' => 'actual value',
                'expectedModel' => new ElementalToScalarComparisonSummary(
                    new ElementIdentifier('.selector'),
                    'excludes',
                    'expected value',
                    'actual value'
                ),
            ],
            'excludes assertion, scalar to scalar comparison' => [
                'assertion' => $assertionParser->parse('$page.title excludes "Page Title"'),
                'expectedValue' => 'Page Title',
                'actualValue' => 'Different Page Title',
                'expectedModel' => new ScalarToScalarComparisonSummary(
                    'excludes',
                    'Page Title',
                    'Different Page Title'
                ),
            ],
            'excludes assertion, scalar to elemental comparison' => [
                'assertion' => $assertionParser->parse('$page.title excludes $".value"'),
                'expectedValue' => 'Page Title',
                'actualValue' => 'Different Page Title',
                'expectedModel' => new ScalarToElementalComparisonSummary(
                    new ElementIdentifier('.value'),
                    'excludes',
                    'Page Title',
                    'Different Page Title'
                ),
            ],
            'excludes assertion, elemental to elemental comparison' => [
                'assertion' => $assertionParser->parse('$".selector" excludes $".value"'),
                'expectedValue' => 'expected value',
                'actualValue' => 'actual value',
                'expectedModel' => new ElementalToElementalComparisonSummary(
                    new ElementIdentifier('.selector'),
                    new ElementIdentifier('.value'),
                    'excludes',
                    'expected value',
                    'actual value'
                ),
            ],
            'matches assertion, elemental to scalar comparison' => [
                'assertion' => $assertionParser->parse('$".selector" matches "/value/"'),
                'expectedValue' => 'expected value',
                'actualValue' => 'actual value',
                'expectedModel' => new ElementalToScalarComparisonSummary(
                    new ElementIdentifier('.selector'),
                    'matches',
                    'expected value',
                    'actual value'
                ),
            ],
            'matches assertion, scalar to scalar comparison' => [
                'assertion' => $assertionParser->parse('$page.title matches "/Page Title/"'),
                'expectedValue' => '/Page Title/',
                'actualValue' => 'Page Title',
                'expectedModel' => new ScalarToScalarComparisonSummary(
                    'matches',
                    '/Page Title/',
                    'Page Title'
                ),
            ],
            'matches assertion, scalar to elemental comparison' => [
                'assertion' => $assertionParser->parse('$page.title matches $".value"'),
                'expectedValue' => 'Page Title',
                'actualValue' => 'Different Page Title',
                'expectedModel' => new ScalarToElementalComparisonSummary(
                    new ElementIdentifier('.value'),
                    'matches',
                    'Page Title',
                    'Different Page Title'
                ),
            ],
            'matches assertion, elemental to elemental comparison' => [
                'assertion' => $assertionParser->parse('$".selector" matches $".value"'),
                'expectedValue' => 'expected value',
                'actualValue' => 'actual value',
                'expectedModel' => new ElementalToElementalComparisonSummary(
                    new ElementIdentifier('.selector'),
                    new ElementIdentifier('.value'),
                    'matches',
                    'expected value',
                    'actual value'
                ),
            ],
            'is-regexp assertion, elemental value' => [
                'assertion' => $assertionParser->parse('$".selector" is-regexp'),
                'expectedValue' => '',
                'actualValue' => 'invalid-regexp',
                'expectedModel' => new ElementalIsRegExpSummary(
                    new ElementIdentifier('.selector'),
                    'invalid-regexp'
                ),
            ],
            'is-regexp assertion, scalar value' => [
                'assertion' => $assertionParser->parse('"invalid-regexp" is-regexp'),
                'expectedValue' => '',
                'actualValue' => 'invalid-regexp',
                'expectedModel' => new ScalarIsRegExpSummary('invalid-regexp'),
            ],
        ];
    }
}
