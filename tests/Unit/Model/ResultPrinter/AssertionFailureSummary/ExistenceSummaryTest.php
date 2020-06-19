<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ExistenceSummary;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;
use webignition\DomElementIdentifier\AttributeIdentifier;
use webignition\DomElementIdentifier\ElementIdentifier;

class ExistenceSummaryTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(ExistenceSummary $existenceSummary, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $existenceSummary->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'element exists' => [
                'existenceSummary' => new ExistenceSummary(
                    new ElementIdentifier('.selector'),
                    'exists'
                ),
                'expectedRenderedString' =>
                    '* Element <comment>$".selector"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  does not exist'
                ,
            ],
            'element not-exists' => [
                'existenceSummary' => new ExistenceSummary(
                    new ElementIdentifier('.selector'),
                    'not-exists'
                ),
                'expectedRenderedString' =>
                    '* Element <comment>$".selector"</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  does exist'
                ,
            ],
            'attribute exists' => [
                'existenceSummary' => new ExistenceSummary(
                    new AttributeIdentifier('.selector', 'attribute_name'),
                    'exists'
                ),
                'expectedRenderedString' =>
                    '* Attribute <comment>$".selector".attribute_name</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
                    '    - attribute name: <comment>attribute_name</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  does not exist'
                ,
            ],
            'attribute not-exists' => [
                'existenceSummary' => new ExistenceSummary(
                    new AttributeIdentifier('.selector', 'attribute_name'),
                    'not-exists'
                ),
                'expectedRenderedString' =>
                    '* Attribute <comment>$".selector".attribute_name</comment> identified by:' . "\n" .
                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
                    '    - attribute name: <comment>attribute_name</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>' . "\n" .
                    '  does exist'
                ,
            ],
        ];
    }
}
