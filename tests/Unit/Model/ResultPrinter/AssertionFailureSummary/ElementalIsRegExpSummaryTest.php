<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ElementalIsRegExpSummary;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;
use webignition\DomElementIdentifier\AttributeIdentifier;
use webignition\DomElementIdentifier\ElementIdentifier;

class ElementalIsRegExpSummaryTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(ElementalIsRegExpSummary $elementalIsRegExpSummary, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $elementalIsRegExpSummary->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'element identifier' => [
                'elementalIsRegExpSummary' => new ElementalIsRegExpSummary(
                    new ElementIdentifier('.selector'),
                    '/invalid/'
                ),
                'expectedRenderedString' =>
                    '* The value of element <comment>$".selector"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  is not a valid regular expression' . "\n" .
                    "\n" .
                    '* <comment>/invalid/</comment> is not a valid regular expression'
                ,
            ],
            'element identifier with ordinal position' => [
                'elementalIsRegExpSummary' => new ElementalIsRegExpSummary(
                    new ElementIdentifier('.selector', 2),
                    '/invalid/'
                ),
                'expectedRenderedString' =>
                    '* The value of element <comment>$".selector":2</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
                    '    - ordinal position: <comment>2</comment>' . "\n" .
                    '  is not a valid regular expression' . "\n" .
                    "\n" .
                    '* <comment>/invalid/</comment> is not a valid regular expression'
                ,
            ],
            'attribute identifier' => [
                'elementalIsRegExpSummary' => new ElementalIsRegExpSummary(
                    new AttributeIdentifier('.selector', 'attribute_name'),
                    '/invalid/'
                ),
                'expectedRenderedString' =>
                    '* The value of attribute <comment>$".selector".attribute_name</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
                    '    - attribute name: <comment>attribute_name</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  is not a valid regular expression' . "\n" .
                    "\n" .
                    '* <comment>/invalid/</comment> is not a valid regular expression'
                ,
            ],
        ];
    }
}
