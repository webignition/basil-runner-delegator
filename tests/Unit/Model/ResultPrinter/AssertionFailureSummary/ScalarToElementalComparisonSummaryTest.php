<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ScalarToElementalComparisonSummary;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;
use webignition\DomElementIdentifier\AttributeIdentifier;
use webignition\DomElementIdentifier\ElementIdentifier;

class ScalarToElementalComparisonSummaryTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(ScalarToElementalComparisonSummary $summary, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $summary->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'is, element value' => [
                'summary' => new ScalarToElementalComparisonSummary(
                    new ElementIdentifier('.value'),
                    'is',
                    'expected',
                    'actual'
                ),
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
            'is, attribute value' => [
                'summary' => new ScalarToElementalComparisonSummary(
                    new AttributeIdentifier('.value', 'attribute_name'),
                    'is',
                    'expected',
                    'actual'
                ),
                'expectedSummary' =>
                    '* <comment>actual</comment> is not equal to the value of attribute '
                    . '<comment>$".value".attribute_name</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.value</comment>' . "\n" .
                    '    - attribute name: <comment>attribute_name</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  with value <comment>expected</comment>' . "\n" .
                    "\n" .
                    '* <comment>actual</comment> is not equal to <comment>expected</comment>'
                ,
            ],
            'is-not' => [
                'summary' => new ScalarToElementalComparisonSummary(
                    new ElementIdentifier('.value'),
                    'is-not',
                    'expected',
                    'expected'
                ),
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
                'summary' => new ScalarToElementalComparisonSummary(
                    new ElementIdentifier('.value'),
                    'includes',
                    'expected',
                    'actual'
                ),
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
                'summary' => new ScalarToElementalComparisonSummary(
                    new ElementIdentifier('.value'),
                    'excludes',
                    'expected',
                    'expected'
                ),
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
                'summary' => new ScalarToElementalComparisonSummary(
                    new ElementIdentifier('.value'),
                    'matches',
                    '/expected/',
                    'actual'
                ),
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
}
