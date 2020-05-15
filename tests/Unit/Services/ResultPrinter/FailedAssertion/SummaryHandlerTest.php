<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter\FailedAssertion;

use webignition\BasilDomIdentifierFactory\Factory as DomIdentifierFactory;
use webignition\BasilModels\Assertion\AssertionInterface;
use webignition\BasilParser\AssertionParser;
use webignition\BasilRunner\Services\ResultPrinter\FailedAssertion\SummaryFactory;
use webignition\BasilRunner\Services\ResultPrinter\FailedAssertion\SummaryHandler;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;
use webignition\DomElementIdentifier\ElementIdentifier;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class SummaryHandlerTest extends AbstractBaseTest
{
    /**
     * @dataProvider handleDataProvider
     */
    public function testHandle(
        AssertionInterface $assertion,
        callable $assertionSummaryLineFactoryCreator,
        string $expectedValue,
        string $actualValue,
        string $expectedSummaryLine
    ) {
        $handler = new SummaryHandler(
            DomIdentifierFactory::createFactory(),
            $assertionSummaryLineFactoryCreator()
        );

        $this->assertEquals(
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
                'assertionSummaryLineFactoryCreator' => function () {
                    $factory = \Mockery::mock(SummaryFactory::class);
                    $factory
                        ->shouldReceive('createForElementalExistenceAssertion')
                        ->withArgs(function (ElementIdentifierInterface $identifier, string $comparison) {
                            $this->assertEquals(
                                new ElementIdentifier('.selector'),
                                $identifier
                            );
                            $this->assertSame('exists', $comparison);

                            return true;
                        })
                        ->andReturn('createForElementalExistenceAssertion');

                    return $factory;
                },
                'expectedValue' => '',
                'actualValue' => '',
                'expectedSummaryLine' => 'createForElementalExistenceAssertion',
            ],
            'not-exists assertion, elemental identifier' => [
                'assertion' => $assertionParser->parse('$".selector" not-exists'),
                'assertionSummaryLineFactoryCreator' => function () {
                    $factory = \Mockery::mock(SummaryFactory::class);
                    $factory
                        ->shouldReceive('createForElementalExistenceAssertion')
                        ->withArgs(function (ElementIdentifierInterface $identifier, string $comparison) {
                            $this->assertEquals(
                                new ElementIdentifier('.selector'),
                                $identifier
                            );
                            $this->assertSame('not-exists', $comparison);

                            return true;
                        })
                        ->andReturn('createForElementalExistenceAssertion');

                    return $factory;
                },
                'expectedValue' => '',
                'actualValue' => '',
                'expectedSummaryLine' => 'createForElementalExistenceAssertion',
            ],
            'is assertion, elemental identifier, scalar value' => [
                'assertion' => $assertionParser->parse('$".selector" is "value"'),
                'assertionSummaryLineFactoryCreator' => function () {
                    $factory = \Mockery::mock(SummaryFactory::class);
                    $factory
                        ->shouldReceive('createForElementalToScalarComparisonAssertion')
                        ->withArgs(function (
                            ElementIdentifierInterface $identifier,
                            string $comparison,
                            string $expectedValue,
                            string $actualValue
                        ) {
                            $this->assertEquals(
                                new ElementIdentifier('.selector'),
                                $identifier
                            );
                            $this->assertSame('is', $comparison);
                            $this->assertSame('expected value', $expectedValue);
                            $this->assertSame('$".selector" is "value" actual value', $actualValue);

                            return true;
                        })
                        ->andReturn('createForElementalExistenceAssertion');

                    return $factory;
                },
                'expectedValue' => 'expected value',
                'actualValue' => '$".selector" is "value" actual value',
                'expectedSummaryLine' => 'createForElementalExistenceAssertion',
            ],
            'is assertion, scalar identifier, scalar value' => [
                'assertion' => $assertionParser->parse('$page.title is "Page Title"'),
                'assertionSummaryLineFactoryCreator' => function () {
                    $factory = \Mockery::mock(SummaryFactory::class);
                    $factory
                        ->shouldReceive('createForScalarToScalarComparisonAssertion')
                        ->withArgs(function (
                            string $identifier,
                            string $comparison,
                            string $expectedValue,
                            string $actualValue
                        ) {
                            $this->assertEquals('$page.title', $identifier);
                            $this->assertSame('is', $comparison);
                            $this->assertSame('Page Title', $expectedValue);
                            $this->assertSame('Different Page Title', $actualValue);

                            return true;
                        })
                        ->andReturn('createForElementalExistenceAssertion');

                    return $factory;
                },
                'expectedValue' => 'Page Title',
                'actualValue' => 'Different Page Title',
                'expectedSummaryLine' => 'createForElementalExistenceAssertion',
            ],
        ];
    }
}
