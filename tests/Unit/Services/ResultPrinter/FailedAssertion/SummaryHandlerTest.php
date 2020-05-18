<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter\FailedAssertion;

use Hamcrest\Core\IsEqual;
use webignition\BasilDomIdentifierFactory\Factory as DomIdentifierFactory;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilParser\AssertionParser;
use webignition\BasilRunner\Services\ResultPrinter\FailedAssertion\SummaryFactory;
use webignition\BasilRunner\Services\ResultPrinter\FailedAssertion\SummaryHandler;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;
use webignition\DomElementIdentifier\ElementIdentifier;

class SummaryHandlerTest extends AbstractBaseTest
{
    /**
     * @dataProvider handleDataProvider
     */
    public function testHandle(
        AssertionInterface $assertion,
        SummaryFactory $summaryFactory,
        string $expectedValue,
        string $actualValue,
        string $expectedSummaryLine
    ) {
        $handler = new SummaryHandler(
            DomIdentifierFactory::createFactory(),
            $summaryFactory
        );

        $this->assertSame(
            $expectedSummaryLine,
            $handler->handle($assertion, $expectedValue, $actualValue)
        );
    }

    public function handleDataProvider(): array
    {
        $assertionParser = AssertionParser::create();

        return [
            'exists assertion, elemental identifier' => [
                'assertion' => $assertionParser->parse('$".selector" exists'),
                'summaryFactory' => $this->createSummaryFactory(
                    'createForElementalExistenceAssertion',
                    [
                        IsEqual::equalTo(new ElementIdentifier('.selector')),
                        'exists'
                    ],
                    'createForElementalExistenceAssertion'
                ),
                'expectedValue' => '',
                'actualValue' => '',
                'expectedSummaryLine' => 'createForElementalExistenceAssertion',
            ],
            'not-exists assertion, elemental identifier' => [
                'assertion' => $assertionParser->parse('$".selector" not-exists'),
                'summaryFactory' => $this->createSummaryFactory(
                    'createForElementalExistenceAssertion',
                    [
                        IsEqual::equalTo(new ElementIdentifier('.selector')),
                        'not-exists'
                    ],
                    'createForElementalNonExistenceAssertion'
                ),
                'expectedValue' => '',
                'actualValue' => '',
                'expectedSummaryLine' => 'createForElementalNonExistenceAssertion',
            ],
            'is assertion, elemental to scalar comparison' => [
                'assertion' => $assertionParser->parse('$".selector" is "value"'),
                'summaryFactory' => $this->createSummaryFactory(
                    'createForElementalToScalarComparisonAssertion',
                    [
                        IsEqual::equalTo(new ElementIdentifier('.selector')),
                        'is',
                        'expected value',
                        'actual value'
                    ],
                    'createForElementalToScalarComparisonAssertion'
                ),
                'expectedValue' => 'expected value',
                'actualValue' => 'actual value',
                'expectedSummaryLine' => 'createForElementalToScalarComparisonAssertion',
            ],
            'is assertion, scalar to scalar comparison' => [
                'assertion' => $assertionParser->parse('$page.title is "Page Title"'),
                'summaryFactory' => $this->createSummaryFactory(
                    'createForScalarToScalarComparisonAssertion',
                    [
                        'is',
                        'Page Title',
                        'Different Page Title'
                    ],
                    'createForScalarToScalarComparisonAssertion'
                ),
                'expectedValue' => 'Page Title',
                'actualValue' => 'Different Page Title',
                'expectedSummaryLine' => 'createForScalarToScalarComparisonAssertion',
            ],
            'is assertion, scalar to elemental comparison' => [
                'assertion' => $assertionParser->parse('$page.title is $".value"'),
                'summaryFactory' => $this->createSummaryFactory(
                    'createForScalarToElementalComparisonAssertion',
                    [
                        IsEqual::equalTo(new ElementIdentifier('.value')),
                        'is',
                        'Page Title',
                        'Different Page Title'
                    ],
                    'createForScalarToElementalComparisonAssertion'
                ),
                'expectedValue' => 'Page Title',
                'actualValue' => 'Different Page Title',
                'expectedSummaryLine' => 'createForScalarToElementalComparisonAssertion',
            ],
            'is assertion, elemental to elemental comparison' => [
                'assertion' => $assertionParser->parse('$".selector" is $".value"'),
                'summaryFactory' => $this->createSummaryFactory(
                    'createForElementalToElementalComparisonAssertion',
                    [
                        IsEqual::equalTo(new ElementIdentifier('.selector')),
                        IsEqual::equalTo(new ElementIdentifier('.value')),
                        'is',
                        'expected value',
                        'actual value'
                    ],
                    'createForElementalToElementalComparisonAssertion'
                ),
                'expectedValue' => 'expected value',
                'actualValue' => 'actual value',
                'expectedSummaryLine' => 'createForElementalToElementalComparisonAssertion',
            ],
            'is-not assertion, elemental to scalar comparison' => [
                'assertion' => $assertionParser->parse('$".selector" is-not "value"'),
                'summaryFactory' => $this->createSummaryFactory(
                    'createForElementalToScalarComparisonAssertion',
                    [
                        IsEqual::equalTo(new ElementIdentifier('.selector')),
                        'is-not',
                        'expected value',
                        'actual value'
                    ],
                    'createForElementalToScalarComparisonAssertion'
                ),
                'expectedValue' => 'expected value',
                'actualValue' => 'actual value',
                'expectedSummaryLine' => 'createForElementalToScalarComparisonAssertion',
            ],
            'is-not assertion, scalar to scalar comparison' => [
                'assertion' => $assertionParser->parse('$page.title is-not "Page Title"'),
                'summaryFactory' => $this->createSummaryFactory(
                    'createForScalarToScalarComparisonAssertion',
                    [
                        'is-not',
                        'Page Title',
                        'Different Page Title'
                    ],
                    'createForScalarToScalarComparisonAssertion'
                ),
                'expectedValue' => 'Page Title',
                'actualValue' => 'Different Page Title',
                'expectedSummaryLine' => 'createForScalarToScalarComparisonAssertion',
            ],
            'is-not assertion, scalar to elemental comparison' => [
                'assertion' => $assertionParser->parse('$page.title is-not $".value"'),
                'summaryFactory' => $this->createSummaryFactory(
                    'createForScalarToElementalComparisonAssertion',
                    [
                        IsEqual::equalTo(new ElementIdentifier('.value')),
                        'is-not',
                        'Page Title',
                        'Different Page Title'
                    ],
                    'createForScalarToElementalComparisonAssertion'
                ),
                'expectedValue' => 'Page Title',
                'actualValue' => 'Different Page Title',
                'expectedSummaryLine' => 'createForScalarToElementalComparisonAssertion',
            ],
            'is-not assertion, elemental to elemental comparison' => [
                'assertion' => $assertionParser->parse('$".selector" is-not $".value"'),
                'summaryFactory' => $this->createSummaryFactory(
                    'createForElementalToElementalComparisonAssertion',
                    [
                        IsEqual::equalTo(new ElementIdentifier('.selector')),
                        IsEqual::equalTo(new ElementIdentifier('.value')),
                        'is-not',
                        'expected value',
                        'actual value'
                    ],
                    'createForElementalToElementalComparisonAssertion'
                ),
                'expectedValue' => 'expected value',
                'actualValue' => 'actual value',
                'expectedSummaryLine' => 'createForElementalToElementalComparisonAssertion',
            ],
            'includes assertion, elemental to scalar comparison' => [
                'assertion' => $assertionParser->parse('$".selector" includes "value"'),
                'summaryFactory' => $this->createSummaryFactory(
                    'createForElementalToScalarComparisonAssertion',
                    [
                        IsEqual::equalTo(new ElementIdentifier('.selector')),
                        'includes',
                        'expected value',
                        'actual value'
                    ],
                    'createForElementalToScalarComparisonAssertion'
                ),
                'expectedValue' => 'expected value',
                'actualValue' => 'actual value',
                'expectedSummaryLine' => 'createForElementalToScalarComparisonAssertion',
            ],
            'includes assertion, scalar to scalar comparison' => [
                'assertion' => $assertionParser->parse('$page.title includes "Page Title"'),
                'summaryFactory' => $this->createSummaryFactory(
                    'createForScalarToScalarComparisonAssertion',
                    [
                        'includes',
                        'Page Title',
                        'Different Page Title'
                    ],
                    'createForScalarToScalarComparisonAssertion'
                ),
                'expectedValue' => 'Page Title',
                'actualValue' => 'Different Page Title',
                'expectedSummaryLine' => 'createForScalarToScalarComparisonAssertion',
            ],
            'includes assertion, scalar to elemental comparison' => [
                'assertion' => $assertionParser->parse('$page.title includes $".value"'),
                'summaryFactory' => $this->createSummaryFactory(
                    'createForScalarToElementalComparisonAssertion',
                    [
                        IsEqual::equalTo(new ElementIdentifier('.value')),
                        'includes',
                        'Page Title',
                        'Different Page Title'
                    ],
                    'createForScalarToElementalComparisonAssertion'
                ),
                'expectedValue' => 'Page Title',
                'actualValue' => 'Different Page Title',
                'expectedSummaryLine' => 'createForScalarToElementalComparisonAssertion',
            ],
            'includes assertion, elemental to elemental comparison' => [
                'assertion' => $assertionParser->parse('$".selector" includes $".value"'),
                'summaryFactory' => $this->createSummaryFactory(
                    'createForElementalToElementalComparisonAssertion',
                    [
                        IsEqual::equalTo(new ElementIdentifier('.selector')),
                        IsEqual::equalTo(new ElementIdentifier('.value')),
                        'includes',
                        'expected value',
                        'actual value'
                    ],
                    'createForElementalToElementalComparisonAssertion'
                ),
                'expectedValue' => 'expected value',
                'actualValue' => 'actual value',
                'expectedSummaryLine' => 'createForElementalToElementalComparisonAssertion',
            ],
            'excludes assertion, elemental to scalar comparison' => [
                'assertion' => $assertionParser->parse('$".selector" excludes "value"'),
                'summaryFactory' => $this->createSummaryFactory(
                    'createForElementalToScalarComparisonAssertion',
                    [
                        IsEqual::equalTo(new ElementIdentifier('.selector')),
                        'excludes',
                        'expected value',
                        'actual value'
                    ],
                    'createForElementalToScalarComparisonAssertion'
                ),
                'expectedValue' => 'expected value',
                'actualValue' => 'actual value',
                'expectedSummaryLine' => 'createForElementalToScalarComparisonAssertion',
            ],
            'excludes assertion, scalar to scalar comparison' => [
                'assertion' => $assertionParser->parse('$page.title excludes "Page Title"'),
                'summaryFactory' => $this->createSummaryFactory(
                    'createForScalarToScalarComparisonAssertion',
                    [
                        'excludes',
                        'Page Title',
                        'Different Page Title'
                    ],
                    'createForScalarToScalarComparisonAssertion'
                ),
                'expectedValue' => 'Page Title',
                'actualValue' => 'Different Page Title',
                'expectedSummaryLine' => 'createForScalarToScalarComparisonAssertion',
            ],
            'excludes assertion, scalar to elemental comparison' => [
                'assertion' => $assertionParser->parse('$page.title excludes $".value"'),
                'summaryFactory' => $this->createSummaryFactory(
                    'createForScalarToElementalComparisonAssertion',
                    [
                        IsEqual::equalTo(new ElementIdentifier('.value')),
                        'excludes',
                        'Page Title',
                        'Different Page Title'
                    ],
                    'createForScalarToElementalComparisonAssertion'
                ),
                'expectedValue' => 'Page Title',
                'actualValue' => 'Different Page Title',
                'expectedSummaryLine' => 'createForScalarToElementalComparisonAssertion',
            ],
            'excludes assertion, elemental to elemental comparison' => [
                'assertion' => $assertionParser->parse('$".selector" excludes $".value"'),
                'summaryFactory' => $this->createSummaryFactory(
                    'createForElementalToElementalComparisonAssertion',
                    [
                        IsEqual::equalTo(new ElementIdentifier('.selector')),
                        IsEqual::equalTo(new ElementIdentifier('.value')),
                        'excludes',
                        'expected value',
                        'actual value'
                    ],
                    'createForElementalToElementalComparisonAssertion'
                ),
                'expectedValue' => 'expected value',
                'actualValue' => 'actual value',
                'expectedSummaryLine' => 'createForElementalToElementalComparisonAssertion',
            ],
            'matches assertion, elemental to scalar comparison' => [
                'assertion' => $assertionParser->parse('$".selector" matches "/value/"'),
                'summaryFactory' => $this->createSummaryFactory(
                    'createForElementalToScalarComparisonAssertion',
                    [
                        IsEqual::equalTo(new ElementIdentifier('.selector')),
                        'matches',
                        'expected value',
                        'actual value'
                    ],
                    'createForElementalToScalarComparisonAssertion'
                ),
                'expectedValue' => 'expected value',
                'actualValue' => 'actual value',
                'expectedSummaryLine' => 'createForElementalToScalarComparisonAssertion',
            ],
            'matches assertion, scalar to scalar comparison' => [
                'assertion' => $assertionParser->parse('$page.title matches "/Page Title/"'),
                'summaryFactory' => $this->createSummaryFactory(
                    'createForScalarToScalarComparisonAssertion',
                    [
                        'matches',
                        'Page Title',
                        'Different Page Title'
                    ],
                    'createForScalarToScalarComparisonAssertion'
                ),
                'expectedValue' => 'Page Title',
                'actualValue' => 'Different Page Title',
                'expectedSummaryLine' => 'createForScalarToScalarComparisonAssertion',
            ],
            'matches assertion, scalar to elemental comparison' => [
                'assertion' => $assertionParser->parse('$page.title matches $".value"'),
                'summaryFactory' => $this->createSummaryFactory(
                    'createForScalarToElementalComparisonAssertion',
                    [
                        IsEqual::equalTo(new ElementIdentifier('.value')),
                        'matches',
                        'Page Title',
                        'Different Page Title'
                    ],
                    'createForScalarToElementalComparisonAssertion'
                ),
                'expectedValue' => 'Page Title',
                'actualValue' => 'Different Page Title',
                'expectedSummaryLine' => 'createForScalarToElementalComparisonAssertion',
            ],
            'matches assertion, elemental to elemental comparison' => [
                'assertion' => $assertionParser->parse('$".selector" matches $".value"'),
                'summaryFactory' => $this->createSummaryFactory(
                    'createForElementalToElementalComparisonAssertion',
                    [
                        IsEqual::equalTo(new ElementIdentifier('.selector')),
                        IsEqual::equalTo(new ElementIdentifier('.value')),
                        'matches',
                        'expected value',
                        'actual value'
                    ],
                    'createForElementalToElementalComparisonAssertion'
                ),
                'expectedValue' => 'expected value',
                'actualValue' => 'actual value',
                'expectedSummaryLine' => 'createForElementalToElementalComparisonAssertion',
            ],
        ];
    }

    /**
     * @param string $methodName
     * @param array<mixed> $args
     * @param string $return
     *
     * @return SummaryFactory
     */
    private function createSummaryFactory(string $methodName, array $args, string $return): SummaryFactory
    {
        $factory = \Mockery::mock(SummaryFactory::class);
        $factory
            ->shouldReceive($methodName)
            ->withArgs($args)
            ->andReturn($return);

        return $factory;
    }
}
