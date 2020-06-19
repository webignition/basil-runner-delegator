<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Services\ResultPrinter\FailedAssertion;

use webignition\BasilRunner\Services\ResultPrinter\FailedAssertion\SummaryFactory;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;
use webignition\DomElementIdentifier\AttributeIdentifier;
use webignition\DomElementIdentifier\ElementIdentifier;
use webignition\DomElementIdentifier\ElementIdentifierInterface;

class SummaryFactoryTest extends AbstractBaseTest
{
    private SummaryFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new SummaryFactory();
    }

    /**
     * @dataProvider createForElementalExistenceAssertionDataProvider
     */
    public function testCreateForElementalExistenceAssertion(
        ElementIdentifierInterface $elementIdentifier,
        string $comparison,
        string $expectedSummary
    ) {
        $this->assertEquals(
            $expectedSummary,
            $this->factory->createForElementalExistenceAssertion($elementIdentifier, $comparison)
        );
    }

    public function createForElementalExistenceAssertionDataProvider(): array
    {
        $grandparentParentChildIdentifier = '$".grandparent":5 >> $".parent":4 >> $".child":3';

        return [
            'non-derived non-descendant element exists assertion, CSS selector, default ordinal position' => [
                'elementIdentifier' => new ElementIdentifier('.selector'),
                'comparison' => 'exists',
                'expectedSummary' =>
                    '* Element <comment>$".selector"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  does not exist'
                ,
            ],
            'non-derived non-descendant element exists assertion, CSS selector, ordinal position 2' => [
                'elementIdentifier' => new ElementIdentifier('.selector', 2),
                'comparison' => 'exists',
                'expectedSummary' =>
                    '* Element <comment>$".selector":2</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
                    '    - ordinal position: <comment>2</comment>' . "\n" .
                    '  does not exist'
                ,
            ],
            'non-derived non-descendant attribute exists assertion, CSS selector, default ordinal position' => [
                'elementIdentifier' => new AttributeIdentifier('.selector', 'attribute_name'),
                'comparison' => 'exists',
                'expectedSummary' =>
                    '* Attribute <comment>$".selector".attribute_name</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
                    '    - attribute name: <comment>attribute_name</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  does not exist'
                ,
            ],
            'non-derived non-descendant element exists assertion, XPath expression, default ordinal position' => [
                'elementIdentifier' => new ElementIdentifier('//div/h1'),
                'comparison' => 'exists',
                'expectedSummary' =>
                    '* Element <comment>$"//div/h1"</comment> identified by:' . "\n" .
                    '    - XPath expression: <comment>//div/h1</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
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
                'expectedSummary' =>
                    '* Element <comment>$".parent" >> $".child"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.child</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with parent:' . "\n" .
                    '    - CSS selector: <comment>.parent</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
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
                'expectedSummary' =>
                    '* Element <comment>' . $grandparentParentChildIdentifier . '</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.child</comment>' . "\n" .
                    '    - ordinal position: <comment>3</comment>' . "\n" .
                    '  with parent:' . "\n" .
                    '    - CSS selector: <comment>.parent</comment>' . "\n" .
                    '    - ordinal position: <comment>4</comment>' . "\n" .
                    '  with parent:' . "\n" .
                    '    - CSS selector: <comment>.grandparent</comment>' . "\n" .
                    '    - ordinal position: <comment>5</comment>' . "\n" .
                    '  does not exist'
                ,
            ],
            'non-derived non-descendant element not-exists assertion' => [
                'elementIdentifier' => new ElementIdentifier('.selector'),
                'comparison' => 'not-exists',
                'expectedSummary' =>
                    '* Element <comment>$".selector"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
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
        string $expectedSummary
    ) {
        $this->assertEquals(
            $expectedSummary,
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
        return [
            'is' => [
                'elementIdentifier' => new ElementIdentifier('.selector'),
                'comparison' => 'is',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummary' =>
                    '* Element <comment>$".selector"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>actual</comment> is not equal to <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>actual</comment> is not equal to <comment>expected</comment>'
                ,
            ],
            'is with descendant identifier' => [
                'elementIdentifier' =>
                    (new ElementIdentifier('.child'))
                        ->withParentIdentifier(new ElementIdentifier('.parent'))
                ,
                'comparison' => 'is',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummary' =>
                    '* Element <comment>$".parent" >> $".child"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.child</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with parent:' . "\n" .
                    '    - CSS selector: <comment>.parent</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>actual</comment> is not equal to <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>actual</comment> is not equal to <comment>expected</comment>'
                ,
            ],
            'is-not' => [
                'elementIdentifier' => new ElementIdentifier('.selector'),
                'comparison' => 'is-not',
                'expectedValue' => 'expected',
                'actualValue' => 'expected',
                'expectedSummary' =>
                    '* Element <comment>$".selector"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>expected</comment> is equal to <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>expected</comment> is equal to <comment>expected</comment>'
                ,
            ],
            'includes' => [
                'elementIdentifier' => new ElementIdentifier('.selector'),
                'comparison' => 'includes',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummary' =>
                    '* Element <comment>$".selector"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>actual</comment> does not include <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>actual</comment> does not include <comment>expected</comment>'
                ,
            ],
            'excludes' => [
                'elementIdentifier' => new ElementIdentifier('.selector'),
                'comparison' => 'excludes',
                'expectedValue' => 'expected',
                'actualValue' => 'expected',
                'expectedSummary' =>
                    '* Element <comment>$".selector"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>expected</comment> does not exclude <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>expected</comment> does not exclude <comment>expected</comment>'
                ,
            ],
            'matches' => [
                'elementIdentifier' => new ElementIdentifier('.selector'),
                'comparison' => 'matches',
                'expectedValue' => '/expected/',
                'actualValue' => 'actual',
                'expectedSummary' =>
                    '* Element <comment>$".selector"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>actual</comment> does not match regular expression '
                    . '<comment>/expected/</comment>' . "\n" .
                    "\n" .
                    '* <comment>actual</comment> does not match regular expression <comment>/expected/</comment>'
                ,
            ],
        ];
    }

    /**
     * @dataProvider createForScalarToScalarComparisonAssertionDataProvider
     */
    public function testCreateScalarToScalarComparisonAssertion(
        string $comparison,
        string $expectedValue,
        string $actualValue,
        string $expectedSummary
    ) {
        $this->assertEquals(
            $expectedSummary,
            $this->factory->createForScalarToScalarComparisonAssertion(
                $comparison,
                $expectedValue,
                $actualValue
            )
        );
    }

    public function createForScalarToScalarComparisonAssertionDataProvider(): array
    {
        return [
            'is' => [
                'comparison' => 'is',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummary' => '* <comment>actual</comment>' . ' is not equal to <comment>expected</comment>',
            ],
            'is-not' => [
                'comparison' => 'is-not',
                'expectedValue' => 'expected',
                'actualValue' => 'expected',
                'expectedSummary' => '* <comment>expected</comment>' . ' is equal to <comment>expected</comment>',
            ],
            'includes' => [
                'comparison' => 'includes',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummary' => '* <comment>actual</comment> does not include <comment>expected</comment>',
            ],
            'excludes' => [
                'comparison' => 'excludes',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummary' => '* <comment>actual</comment> does not exclude <comment>expected</comment>',
            ],
            'matches' => [
                'comparison' => 'matches',
                'expectedValue' => '/expected/',
                'actualValue' => 'actual',
                'expectedSummary' =>
                    '* <comment>actual</comment> does not match regular expression <comment>/expected/</comment>'
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
        string $expectedSummary
    ) {
        $this->assertEquals(
            $expectedSummary,
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
        return [
            'is, element identifier, element value' => [
                'identifier' => new ElementIdentifier('.identifier'),
                'valueIdentifier' => new ElementIdentifier('.value'),
                'comparison' => 'is',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummary' =>
                    '* Element <comment>$".identifier"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.identifier</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>actual</comment> is not equal to the value of element '
                    . '<comment>$".value"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.value</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>actual</comment> is not equal to <comment>expected</comment>'
                ,
            ],
            'is, descendant element identifier, element value' => [
                'identifier' =>
                    (new ElementIdentifier('.child'))
                        ->withParentIdentifier(new ElementIdentifier('.parent'))
                ,
                'valueIdentifier' => new ElementIdentifier('.value'),
                'comparison' => 'is',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummary' =>
                    '* Element <comment>$".parent" >> $".child"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.child</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with parent:' . "\n" .
                    '    - CSS selector: <comment>.parent</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>actual</comment> is not equal to the value of element '
                    . '<comment>$".value"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.value</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>actual</comment> is not equal to <comment>expected</comment>'
                ,
            ],
            'is, descendant element identifier, descendant element value' => [
                'identifier' =>
                    (new ElementIdentifier('.identifier-child'))
                        ->withParentIdentifier(new ElementIdentifier('.identifier-parent'))
                ,
                'valueIdentifier' =>
                    (new ElementIdentifier('.value-child'))
                        ->withParentIdentifier(new ElementIdentifier('.value-parent'))
                ,
                'comparison' => 'is',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummary' =>
                    '* Element <comment>$".identifier-parent" >> $".identifier-child"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.identifier-child</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with parent:' . "\n" .
                    '    - CSS selector: <comment>.identifier-parent</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>actual</comment> is not equal to the value of element '
                    . '<comment>$".value-parent" >> $".value-child"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.value-child</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with parent:' . "\n" .
                    '    - CSS selector: <comment>.value-parent</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>actual</comment> is not equal to <comment>expected</comment>'
                ,
            ],
            'is, element identifier, attribute value' => [
                'identifier' => new ElementIdentifier('.identifier'),
                'valueIdentifier' => new AttributeIdentifier('.value', 'attribute_name'),
                'comparison' => 'is',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummary' =>
                    '* Element <comment>$".identifier"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.identifier</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>actual</comment> is not equal to the value of attribute '
                    . '<comment>$".value".attribute_name</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.value</comment>' . "\n" .
                    '    - attribute name: <comment>attribute_name</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>actual</comment> is not equal to <comment>expected</comment>'
                ,
            ],
            'is, attribute identifier, element value' => [
                'identifier' => new AttributeIdentifier('.identifier', 'attribute_name'),
                'valueIdentifier' => new ElementIdentifier('.value'),
                'comparison' => 'is',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummary' =>
                    '* Attribute <comment>$".identifier".attribute_name</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.identifier</comment>' . "\n" .
                    '    - attribute name: <comment>attribute_name</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>actual</comment> is not equal to the value of element '
                    . '<comment>$".value"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.value</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>actual</comment> is not equal to <comment>expected</comment>'
                ,
            ],
            'is, attribute identifier, attribute value' => [
                'identifier' => new AttributeIdentifier('.identifier', 'identifier_attribute'),
                'valueIdentifier' => new AttributeIdentifier('.value', 'value_attribute'),
                'comparison' => 'is',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummary' =>
                    '* Attribute <comment>$".identifier".identifier_attribute</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.identifier</comment>' . "\n" .
                    '    - attribute name: <comment>identifier_attribute</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>actual</comment> is not equal to the value of attribute '
                    . '<comment>$".value".value_attribute</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.value</comment>' . "\n" .
                    '    - attribute name: <comment>value_attribute</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>actual</comment> is not equal to <comment>expected</comment>'
                ,
            ],
            'is-not' => [
                'identifier' => new ElementIdentifier('.identifier'),
                'valueIdentifier' => new ElementIdentifier('.value'),
                'comparison' => 'is-not',
                'expectedValue' => 'expected',
                'actualValue' => 'expected',
                'expectedSummary' =>
                    '* Element <comment>$".identifier"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.identifier</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>expected</comment> is equal to the value of element '
                    . '<comment>$".value"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.value</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>expected</comment> is equal to <comment>expected</comment>'
                ,
            ],
            'includes' => [
                'identifier' => new ElementIdentifier('.identifier'),
                'valueIdentifier' => new ElementIdentifier('.value'),
                'comparison' => 'includes',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummary' =>
                    '* Element <comment>$".identifier"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.identifier</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>actual</comment> does not include the value of element '
                    . '<comment>$".value"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.value</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>actual</comment> does not include <comment>expected</comment>'
                ,
            ],
            'excludes' => [
                'identifier' => new ElementIdentifier('.identifier'),
                'valueIdentifier' => new ElementIdentifier('.value'),
                'comparison' => 'excludes',
                'expectedValue' => 'expected',
                'actualValue' => 'expected',
                'expectedSummary' =>
                    '* Element <comment>$".identifier"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.identifier</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>expected</comment> does not exclude the value of element '
                    . '<comment>$".value"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.value</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>expected</comment> does not exclude <comment>expected</comment>'
                ,
            ],
            'matches' => [
                'identifier' => new ElementIdentifier('.identifier'),
                'valueIdentifier' => new ElementIdentifier('.value'),
                'comparison' => 'matches',
                'expectedValue' => '/expected/',
                'actualValue' => 'actual',
                'expectedSummary' =>
                    '* Element <comment>$".identifier"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.identifier</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>actual</comment> does not match regular expression the value of element '
                    . '<comment>$".value"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.value</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>/expected/</comment>' . "\n" .
                    "\n" .
                    '* <comment>actual</comment> does not match regular expression <comment>/expected/</comment>'
                ,
            ],
        ];
    }

    /**
     * @dataProvider createForScalarToElementalComparisonDataProvider
     */
    public function testCreateForScalarToElementalComparison(
        ElementIdentifierInterface $valueIdentifier,
        string $comparison,
        string $expectedValue,
        string $actualValue,
        string $expectedSummary
    ) {
        $this->assertSame(
            $expectedSummary,
            $this->factory->createForScalarToElementalComparisonAssertion(
                $valueIdentifier,
                $comparison,
                $expectedValue,
                $actualValue
            )
        );
    }

    public function createForScalarToElementalComparisonDataProvider(): array
    {
        return [
            'is' => [
                'valueIdentifier' => new ElementIdentifier('.value'),
                'comparison' => 'is',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummary' =>
                    '* <comment>actual</comment> is not equal to the value of element '
                    . '<comment>$".value"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.value</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>actual</comment> is not equal to <comment>expected</comment>'
                ,
            ],
            'is, descendant identifier' => [
                'valueIdentifier' =>
                    (new ElementIdentifier('.child'))
                        ->withParentIdentifier(new ElementIdentifier('.parent'))
                ,
                'comparison' => 'is',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummary' =>
                    '* <comment>actual</comment> is not equal to the value of element '
                    . '<comment>$".parent" >> $".child"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.child</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with parent:' . "\n" .
                    '    - CSS selector: <comment>.parent</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>actual</comment> is not equal to <comment>expected</comment>'
                ,
            ],
            'is-not' => [
                'valueIdentifier' => new ElementIdentifier('.value'),
                'comparison' => 'is-not',
                'expectedValue' => 'expected',
                'actualValue' => 'expected',
                'expectedSummary' =>
                    '* <comment>expected</comment> is equal to the value of element '
                    . '<comment>$".value"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.value</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>expected</comment> is equal to <comment>expected</comment>'
                ,
            ],
            'includes' => [
                'valueIdentifier' => new ElementIdentifier('.value'),
                'comparison' => 'includes',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedSummary' =>
                    '* <comment>actual</comment> does not include the value of element '
                    . '<comment>$".value"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.value</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>actual</comment> does not include <comment>expected</comment>'
                ,
            ],
            'excludes' => [
                'valueIdentifier' => new ElementIdentifier('.value'),
                'comparison' => 'excludes',
                'expectedValue' => 'expected',
                'actualValue' => 'expected',
                'expectedSummary' =>
                    '* <comment>expected</comment> does not exclude the value of element '
                    . '<comment>$".value"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.value</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>expected</comment> does not exclude <comment>expected</comment>'
                ,
            ],
            'matches' => [
                'valueIdentifier' => new ElementIdentifier('.value'),
                'comparison' => 'matches',
                'expectedValue' => '/expected/',
                'actualValue' => 'actual',
                'expectedSummary' =>
                    '* <comment>actual</comment> does not match regular expression the value of element '
                    . '<comment>$".value"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.value</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>/expected/</comment>' . "\n" .
                    "\n" .
                    '* <comment>actual</comment> does not match regular expression <comment>/expected/</comment>'
                ,
            ],
        ];
    }

    /**
     * @dataProvider createForElementalIsRegExpAssertionDataProvider
     */
    public function testCreateForElementalIsRegExpAssertion(
        ElementIdentifierInterface $identifier,
        string $regexp,
        string $expectedSummary
    ) {
        $this->assertSame(
            $expectedSummary,
            $this->factory->createForElementalIsRegExpAssertion(
                $identifier,
                $regexp
            )
        );
    }

    public function createForElementalIsRegExpAssertionDataProvider(): array
    {
        return [
            'non-descendant identifier' => [
                'identifier' => new ElementIdentifier('.selector'),
                'regexp' => 'invalid-regex',
                'expectedSummary' =>
                    '* The value of element <comment>$".selector"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  is not a valid regular expression' . "\n" .
                    "\n" .
                    '* <comment>invalid-regex</comment> is not a valid regular expression'
                ,
            ],
            'descendant identifier' => [
                'elementIdentifier' =>
                    (new ElementIdentifier('.child'))
                        ->withParentIdentifier(
                            new ElementIdentifier('.parent')
                        ),
                'regexp' => 'invalid-regex',
                'expectedSummary' =>
                    '* The value of element <comment>$".parent" >> $".child"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.child</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with parent:' . "\n" .
                    '    - CSS selector: <comment>.parent</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  is not a valid regular expression' . "\n" .
                    "\n" .
                    '* <comment>invalid-regex</comment> is not a valid regular expression'
                ,
            ],
        ];
    }

    /**
     * @dataProvider createForScalarsRegExpAssertionDataProvider
     */
    public function testCreateForScalarIsRegExpAssertion(
        string $regexp,
        string $expectedSummary
    ) {
        $this->assertSame(
            $expectedSummary,
            $this->factory->createForScalarIsRegExpAssertion($regexp)
        );
    }

    public function createForScalarsRegExpAssertionDataProvider(): array
    {
        return [
            'default' => [
                'regexp' => 'invalid-regex',
                'expectedSummary' => '* <comment>invalid-regex</comment> is not a valid regular expression',
            ],
        ];
    }
}
