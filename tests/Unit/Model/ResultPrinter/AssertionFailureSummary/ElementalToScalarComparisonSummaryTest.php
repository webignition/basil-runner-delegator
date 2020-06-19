<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ElementalToScalarComparisonSummary;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;
use webignition\DomElementIdentifier\AttributeIdentifier;
use webignition\DomElementIdentifier\ElementIdentifier;

class ElementalToScalarComparisonSummaryTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(ElementalToScalarComparisonSummary $summary, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $summary->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'is, element identifier' => [
                'summary' => new ElementalToScalarComparisonSummary(
                    new ElementIdentifier('.identifier'),
                    'is',
                    'expected',
                    'actual'
                ),
                'expectedSummary' =>
                    '* Element <comment>$".identifier"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.identifier</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>actual</comment> is not equal to <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>actual</comment> is not equal to <comment>expected</comment>'
                ,
            ],
            'is, attribute identifier' => [
                'summary' => new ElementalToScalarComparisonSummary(
                    new AttributeIdentifier('.identifier', 'attribute_name'),
                    'is',
                    'expected',
                    'actual'
                ),
                'expectedSummary' =>
                    '* Attribute <comment>$".identifier".attribute_name</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.identifier</comment>' . "\n" .
                    '    - attribute name: <comment>attribute_name</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>actual</comment> is not equal to <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>actual</comment> is not equal to <comment>expected</comment>'
                ,
            ],
            'is-not' => [
                'summary' => new ElementalToScalarComparisonSummary(
                    new ElementIdentifier('.identifier'),
                    'is-not',
                    'expected',
                    'expected'
                ),
                'expectedSummary' =>
                    '* Element <comment>$".identifier"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.identifier</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>expected</comment> is equal to <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>expected</comment> is equal to <comment>expected</comment>'
                ,
            ],
            'includes' => [
                'summary' => new ElementalToScalarComparisonSummary(
                    new ElementIdentifier('.identifier'),
                    'includes',
                    'expected',
                    'actual'
                ),
                'expectedSummary' =>
                    '* Element <comment>$".identifier"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.identifier</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>actual</comment> does not include <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>actual</comment> does not include <comment>expected</comment>'
                ,
            ],
            'excludes' => [
                'summary' => new ElementalToScalarComparisonSummary(
                    new ElementIdentifier('.identifier'),
                    'excludes',
                    'expected',
                    'expected'
                ),
                'expectedSummary' =>
                    '* Element <comment>$".identifier"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.identifier</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>expected</comment> does not exclude <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>expected</comment> does not exclude <comment>expected</comment>'
                ,
            ],
            'matches' => [
                'summary' => new ElementalToScalarComparisonSummary(
                    new ElementIdentifier('.identifier'),
                    'matches',
                    '/expected/',
                    'actual'
                ),
                'expectedSummary' =>
                    '* Element <comment>$".identifier"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.identifier</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>actual</comment> does not match regular expression '
                    . '<comment>/expected/</comment>' . "\n" .
                    "\n" .
                    '* <comment>actual</comment> does not match regular expression <comment>/expected/</comment>'
                ,
            ],
        ];
    }

    public function createForElementalToScalarComparisonAssertionDataProvider(): array
    {
        return [
            'is' => [
                'elementIdentifier' => new ElementIdentifier('.selector'),
                'operator' => 'is',
                'expectedValue' => 'expected',
                'actualValue' => 'actual',
                'expectedModel' => new ElementalToScalarComparisonSummary(
                    new ElementIdentifier('.selector'),
                    'is',
                    'expected',
                    'actual'
                ),
            ],
//            'is with descendant identifier' => [
//                'elementIdentifier' =>
//                    (new ElementIdentifier('.child'))
//                        ->withParentIdentifier(new ElementIdentifier('.parent'))
//                ,
//                'operator' => 'is',
//                'expectedValue' => 'expected',
//                'actualValue' => 'actual',
//                'expectedSummary' =>
//                    '* Element <comment>$".parent" >> $".child"</comment> identified by:' . "\n" .
//                    '    - CSS selector: <comment>.child</comment>' . "\n" .
//                    '    - ordinal position: <comment>1</comment>' . "\n" .
//                    '  with parent:' . "\n" .
//                    '    - CSS selector: <comment>.parent</comment>' . "\n" .
//                    '    - ordinal position: <comment>1</comment>' . "\n" .
//                    '  with value <comment>actual</comment> is not equal to <comment>expected</comment>' . "\n" .
//                    "\n" .
//                    '* <comment>actual</comment> is not equal to <comment>expected</comment>'
//                ,
//            ],
//            'is-not' => [
//                'elementIdentifier' => new ElementIdentifier('.selector'),
//                'operator' => 'is-not',
//                'expectedValue' => 'expected',
//                'actualValue' => 'expected',
//                'expectedSummary' =>
//                    '* Element <comment>$".selector"</comment> identified by:' . "\n" .
//                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
//                    '    - ordinal position: <comment>1</comment>' . "\n" .
//                    '  with value <comment>expected</comment> is equal to <comment>expected</comment>' . "\n" .
//                    "\n" .
//                    '* <comment>expected</comment> is equal to <comment>expected</comment>'
//                ,
//            ],
//            'includes' => [
//                'elementIdentifier' => new ElementIdentifier('.selector'),
//                'operator' => 'includes',
//                'expectedValue' => 'expected',
//                'actualValue' => 'actual',
//                'expectedSummary' =>
//                    '* Element <comment>$".selector"</comment> identified by:' . "\n" .
//                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
//                    '    - ordinal position: <comment>1</comment>' . "\n" .
//                    '  with value <comment>actual</comment> does not include <comment>expected</comment>' . "\n" .
//                    "\n" .
//                    '* <comment>actual</comment> does not include <comment>expected</comment>'
//                ,
//            ],
//            'excludes' => [
//                'elementIdentifier' => new ElementIdentifier('.selector'),
//                'operator' => 'excludes',
//                'expectedValue' => 'expected',
//                'actualValue' => 'expected',
//                'expectedSummary' =>
//                    '* Element <comment>$".selector"</comment> identified by:' . "\n" .
//                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
//                    '    - ordinal position: <comment>1</comment>' . "\n" .
//                    '  with value <comment>expected</comment> does not exclude <comment>expected</comment>' . "\n" .
//                    "\n" .
//                    '* <comment>expected</comment> does not exclude <comment>expected</comment>'
//                ,
//            ],
//            'matches' => [
//                'elementIdentifier' => new ElementIdentifier('.selector'),
//                'operator' => 'matches',
//                'expectedValue' => '/expected/',
//                'actualValue' => 'actual',
//                'expectedSummary' =>
//                    '* Element <comment>$".selector"</comment> identified by:' . "\n" .
//                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
//                    '    - ordinal position: <comment>1</comment>' . "\n" .
//                    '  with value <comment>actual</comment> does not match regular expression '
//                    . '<comment>/expected/</comment>' . "\n" .
//                    "\n" .
//                    '* <comment>actual</comment> does not match regular expression <comment>/expected/</comment>'
//                ,
//            ],
        ];
    }
}
