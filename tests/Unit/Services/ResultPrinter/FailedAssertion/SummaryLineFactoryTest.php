<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter\FailedAssertion;

use webignition\BasilRunner\Services\ResultPrinter\ConsoleOutputFactory;
use webignition\BasilRunner\Services\ResultPrinter\FailedAssertion\SummaryLineFactory;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;
use webignition\DomElementIdentifier\AttributeIdentifier;
use webignition\DomElementIdentifier\ElementIdentifier;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class SummaryLineFactoryTest extends AbstractBaseTest
{
    /**
     * @var SummaryLineFactory
     */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new SummaryLineFactory(
            new ConsoleOutputFactory()
        );
    }

    /**
     * @dataProvider createForElementalExistenceAssertionDataProvider
     */
    public function testCreateForElementalExistenceAssertion(
        ElementIdentifierInterface $elementIdentifier,
        string $comparison,
        string $expectedSummaryLine
    ) {
        $this->assertEquals(
            $expectedSummaryLine,
            $this->factory->createForElementalExistenceAssertion($elementIdentifier, $comparison)
        );
    }

    public function createForElementalExistenceAssertionDataProvider(): array
    {
        $cof = new ConsoleOutputFactory();
        $grandparentParentChildIdentifier = '$".grandparent":5 >> $".parent":4 >> $".child":3';

        return [
            'non-derived non-descendant element exists assertion, CSS selector, default ordinal position' => [
                'elementIdentifier' => new ElementIdentifier('.selector'),
                'comparison' => 'exists',
                'expectedSummaryLine' =>
                    '* Element ' . $cof->createComment('$".selector"') . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.selector') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('1') . "\n" .
                    '  does not exist'
                ,
            ],
            'non-derived non-descendant element exists assertion, CSS selector, ordinal position 2' => [
                'elementIdentifier' => new ElementIdentifier('.selector', 2),
                'comparison' => 'exists',
                'expectedSummaryLine' =>
                    '* Element ' . $cof->createComment('$".selector":2') . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.selector') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('2') . "\n" .
                    '  does not exist'
                ,
            ],
            'non-derived non-descendant attribute exists assertion, CSS selector, default ordinal position' => [
                'elementIdentifier' => new AttributeIdentifier('.selector', 'attribute_name'),
                'comparison' => 'exists',
                'expectedSummaryLine' =>
                    '* Attribute ' . $cof->createComment('$".selector".attribute_name') . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.selector') . "\n" .
                    '    - attribute name: ' . $cof->createComment('attribute_name') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('1') . "\n" .
                    '  does not exist'
                ,
            ],
            'non-derived non-descendant element exists assertion, XPath expression, default ordinal position' => [
                'elementIdentifier' => new ElementIdentifier('//div/h1'),
                'comparison' => 'exists',
                'expectedSummaryLine' =>
                    '* Element ' . $cof->createComment('$"//div/h1"') . ' identified by:' . "\n" .
                    '    - XPath expression: ' . $cof->createComment('//div/h1') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('1') . "\n" .
                    '  does not exist'
                ,
            ],
            'non-derived descendant element exists assertion (parent child)' => [
                'elementIdentifier' =>
                    (new ElementIdentifier('.child'))
                        ->withParentIdentifier(
                            new ElementIdentifier('.parent')
                        ),
                'comparison' => 'exists',
                'expectedSummaryLine' =>
                    '* Element ' . $cof->createComment('$".parent" >> $".child"') . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.child') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('1') . "\n" .
                    '  with parent:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.parent') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('1') . "\n" .
                    '  does not exist'
                ,
            ],
            'non-derived descendant element exists assertion (grandparent parent child)' => [
                'elementIdentifier' =>
                    (new ElementIdentifier('.child', 3))
                        ->withParentIdentifier(
                            (new ElementIdentifier('.parent', 4))
                                ->withParentIdentifier(
                                    new ElementIdentifier('.grandparent', 5)
                                )
                        ),
                'comparison' => 'exists',
                'expectedSummaryLine' =>
                    '* Element ' . $cof->createComment($grandparentParentChildIdentifier) . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.child') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('3') . "\n" .
                    '  with parent:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.parent') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('4') . "\n" .
                    '  with parent:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.grandparent') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('5') . "\n" .
                    '  does not exist'
                ,
            ],
            'non-derived non-descendant element not-exists assertion' => [
                'elementIdentifier' => new ElementIdentifier('.selector'),
                'comparison' => 'not-exists',
                'expectedSummaryLine' =>
                    '* Element ' . $cof->createComment('$".selector"') . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.selector') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('1') . "\n" .
                    '  does exist'
                ,
            ],
        ];
    }

    /**
     * @dataProvider createForElementalToScalarComparisonAssertionDataProvider
     */
    public function testCreateForElementalToScalarComparisonAssertion(
        ElementIdentifierInterface $elementIdentifier,
        string $comparison,
        string $expectedValue,
        string $actualValue,
        string $expectedSummaryLine
    ) {
        $this->assertEquals(
            $expectedSummaryLine,
            $this->factory->createForElementalToScalarComparisonAssertion(
                $elementIdentifier,
                $comparison,
                $expectedValue,
                $actualValue
            )
        );
    }

    public function createForElementalToScalarComparisonAssertionDataProvider(): array
    {
        $consoleOutputFactory = new ConsoleOutputFactory();

        return [
            'is' => [
                'elementIdentifier' => new ElementIdentifier('.selector'),
                'comparison' => 'is',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummaryLine' =>
                    '* Element ' . $consoleOutputFactory->createComment('$".selector"') . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $consoleOutputFactory->createComment('.selector') . "\n" .
                    '    - ordinal position: ' . $consoleOutputFactory->createComment('1') . "\n" .
                    '  is not equal to expected value' . "\n" .
                    '  - expected: ' . $consoleOutputFactory->createComment('expected') . "\n" .
                    '  - actual:   ' . $consoleOutputFactory->createComment('actual')
                ,
            ],
        ];
    }

    /**
     * @dataProvider createForScalarToScalarComparisonAssertionDataProvider
     */
    public function testCreateScalarToScalarComparisonAssertion(
        string $identifier,
        string $comparison,
        string $expectedValue,
        string $actualValue,
        string $expectedSummaryLine
    ) {
        $this->assertEquals(
            $expectedSummaryLine,
            $this->factory->createForScalarToScalarComparisonAssertion(
                $identifier,
                $comparison,
                $expectedValue,
                $actualValue
            )
        );
    }

    public function createForScalarToScalarComparisonAssertionDataProvider(): array
    {
        $consoleOutputFactory = new ConsoleOutputFactory();

        return [
            'is' => [
                'identifier' => '$page.title',
                'comparison' => 'is',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummaryLine' =>
                    '* $page.title is not equal to expected value' . "\n" .
                    '  - expected: ' . $consoleOutputFactory->createComment('expected') . "\n" .
                    '  - actual:   ' . $consoleOutputFactory->createComment('actual')
                ,
            ],
        ];
    }

    /**
     * @dataProvider createForElementalToElementalComparisonAssertionDataProvider
     */
    public function testCreateForElementalToElementalComparisonAssertion(
        ElementIdentifierInterface $identifier,
        ElementIdentifierInterface $valueIdentifier,
        string $comparison,
        string $expectedValue,
        string $actualValue,
        string $expectedSummaryLine
    ) {
        $this->assertEquals(
            $expectedSummaryLine,
            $this->factory->createForElementalToElementalComparisonAssertion(
                $identifier,
                $valueIdentifier,
                $comparison,
                $expectedValue,
                $actualValue
            )
        );
    }

    public function createForElementalToElementalComparisonAssertionDataProvider(): array
    {
        $cof = new ConsoleOutputFactory();

        return [
            'is' => [
                'identifier' => new ElementIdentifier('.identifier'),
                'valueIdentifier' => new ElementIdentifier('.value'),
                'comparison' => 'is',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummaryLine' =>
                    '* Element ' . $cof->createComment('$".identifier"') . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.identifier') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('1') . "\n" .
                    '  is not equal to element ' . $cof->createComment('$".value"') . ' identified by:' . "\n" .
                    '    - CSS selector: ' . $cof->createComment('.value') . "\n" .
                    '    - ordinal position: ' . $cof->createComment('1') . "\n" .
                    "\n" .
                    '  - expected: ' . $cof->createComment('expected') . "\n" .
                    '  - actual:   ' . $cof->createComment('actual')
                ,
            ],
        ];
    }
}
