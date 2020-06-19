<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ElementalToElementalComparisonSummary;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;
use webignition\DomElementIdentifier\AttributeIdentifier;
use webignition\DomElementIdentifier\ElementIdentifier;

class ElementalToElementalComparisonSummaryTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(ElementalToElementalComparisonSummary $summary, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $summary->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'is, element identifier, element value' => [
                'summary' => new ElementalToElementalComparisonSummary(
                    new ElementIdentifier('.identifier'),
                    new ElementIdentifier('.value'),
                    'is',
                    'expected',
                    'actual'
                ),
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
            'is, element identifier, attribute value' => [
                'summary' => new ElementalToElementalComparisonSummary(
                    new ElementIdentifier('.identifier'),
                    new AttributeIdentifier('.value', 'attribute_name'),
                    'is',
                    'expected',
                    'actual'
                ),
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
                'summary' => new ElementalToElementalComparisonSummary(
                    new AttributeIdentifier('.identifier', 'attribute_name'),
                    new ElementIdentifier('.value'),
                    'is',
                    'expected',
                    'actual'
                ),
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
                'summary' => new ElementalToElementalComparisonSummary(
                    new AttributeIdentifier('.identifier', 'identifier_attribute'),
                    new AttributeIdentifier('.value', 'value_attribute'),
                    'is',
                    'expected',
                    'actual'
                ),
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
                'summary' => new ElementalToElementalComparisonSummary(
                    new ElementIdentifier('.identifier'),
                    new ElementIdentifier('.value'),
                    'is-not',
                    'expected',
                    'expected'
                ),
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
                'summary' => new ElementalToElementalComparisonSummary(
                    new ElementIdentifier('.identifier'),
                    new ElementIdentifier('.value'),
                    'includes',
                    'expected',
                    'actual'
                ),
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
                'summary' => new ElementalToElementalComparisonSummary(
                    new ElementIdentifier('.identifier'),
                    new ElementIdentifier('.value'),
                    'excludes',
                    'expected',
                    'expected'
                ),
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
                'summary' => new ElementalToElementalComparisonSummary(
                    new ElementIdentifier('.identifier'),
                    new ElementIdentifier('.value'),
                    'matches',
                    '/expected/',
                    'actual'
                ),
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
}
