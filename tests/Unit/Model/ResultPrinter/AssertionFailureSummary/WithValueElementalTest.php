<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\WithValueElemental;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;
use webignition\DomElementIdentifier\AttributeIdentifier;
use webignition\DomElementIdentifier\ElementIdentifier;

class WithValueElementalTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(WithValueElemental $withValueElemental, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $withValueElemental->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'is, element value' => [
                'withValueElemental' => new WithValueElemental(
                    'actual',
                    new ElementIdentifier('.expected'),
                    'is'
                ),
                'expectedRenderedString' =>
                    'with value <comment>actual</comment> is not equal to the value of element ' .
                    '<comment>$".expected"</comment> identified by:',
            ],
            'is, element value, indented' => [
                'withValueElemental' => new WithValueElemental(
                    'actual',
                    new ElementIdentifier('.expected'),
                    'is',
                    1
                ),
                'expectedRenderedString' =>
                    '  with value <comment>actual</comment> is not equal to the value of element ' .
                    '<comment>$".expected"</comment> identified by:',
            ],
            'is, attribute value' => [
                'withValueElemental' => new WithValueElemental(
                    'actual',
                    new AttributeIdentifier('.expected', 'attribute_name'),
                    'is'
                ),
                'expectedRenderedString' =>
                    'with value <comment>actual</comment> is not equal to the value of attribute ' .
                    '<comment>$".expected".attribute_name</comment> identified by:',
            ],
            'is-not' => [
                'withValueElemental' => new WithValueElemental(
                    'actual',
                    new ElementIdentifier('.expected'),
                    'is-not'
                ),
                'expectedRenderedString' =>
                    'with value <comment>actual</comment> is equal to the value of element ' .
                    '<comment>$".expected"</comment> identified by:',
            ],
            'includes' => [
                'withValueElemental' => new WithValueElemental(
                    'actual',
                    new ElementIdentifier('.expected'),
                    'includes'
                ),
                'expectedRenderedString' =>
                    'with value <comment>actual</comment> does not include the value of element ' .
                    '<comment>$".expected"</comment> identified by:',
            ],
            'excludes' => [
                'withValueElemental' => new WithValueElemental(
                    'actual',
                    new ElementIdentifier('.expected'),
                    'excludes'
                ),
                'expectedRenderedString' =>
                    'with value <comment>actual</comment> does not exclude the value of element ' .
                    '<comment>$".expected"</comment> identified by:',
            ],
            'matches' => [
                'withValueElemental' => new WithValueElemental(
                    'actual',
                    new ElementIdentifier('.expected'),
                    'matches'
                ),
                'expectedRenderedString' =>
                    'with value <comment>actual</comment> does not match regular expression the value of element ' .
                    '<comment>$".expected"</comment> identified by:',
            ],
        ];
    }
}
