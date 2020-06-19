<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\IdentifierProperties;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;
use webignition\DomElementIdentifier\AttributeIdentifier;
use webignition\DomElementIdentifier\ElementIdentifier;

class IdentifierPropertiesTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(IdentifierProperties $identifierProperties, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $identifierProperties->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'element' => [
                'identifierProperties' => new IdentifierProperties(
                    new ElementIdentifier('.selector')
                ),
                'expectedRenderedString' =>
                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>'
                ,
            ],
            'element with ordinal position' => [
                'identifierProperties' => new IdentifierProperties(
                    new ElementIdentifier('.selector', 2)
                ),
                'expectedRenderedString' =>
                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
                    '    - ordinal position: <comment>2</comment>'
                ,
            ],
            'attribute' => [
                'identifierProperties' => new IdentifierProperties(
                    new AttributeIdentifier('.selector', 'attribute_name')
                ),
                'expectedRenderedString' =>
                    '    - CSS selector: <comment>.selector</comment>' . "\n" .
                    '    - attribute name: <comment>attribute_name</comment>' . "\n" .
                    '    - ordinal position: <comment>1</comment>'
                ,
            ],
        ];
    }
}
