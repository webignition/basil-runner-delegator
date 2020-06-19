<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter\AssertionFailureSummary;

use webignition\BasilRunner\Model\ResultPrinter\AssertionFailureSummary\ScalarValueComparedToElementalValue;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;
use webignition\DomElementIdentifier\AttributeIdentifier;
use webignition\DomElementIdentifier\ElementIdentifier;

class ScalarValueComparedToElementalValueTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(ScalarValueComparedToElementalValue $model, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $model->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'is, element value' => [
                'summary' => new ScalarValueComparedToElementalValue(
                    'actual',
                    'is',
                    new ElementIdentifier('.value'),
                ),
                'expectedSummary' =>
                    '* <comment>actual</comment> is not equal to the value of element '
                    . '<comment>$".value"</comment> identified by:'
                ,
            ],
            'is, attribute value' => [
                'summary' => new ScalarValueComparedToElementalValue(
                    'actual',
                    'is',
                    new AttributeIdentifier('.value', 'attribute_name')
                ),
                'expectedSummary' =>
                    '* <comment>actual</comment> is not equal to the value of attribute '
                    . '<comment>$".value".attribute_name</comment> identified by:'
                ,
            ],
            'is-not' => [
                'summary' => new ScalarValueComparedToElementalValue(
                    'expected',
                    'is-not',
                    new ElementIdentifier('.value')
                ),
                'expectedSummary' =>
                    '* <comment>expected</comment> is equal to the value of element '
                    . '<comment>$".value"</comment> identified by:'
                ,
            ],
            'includes' => [
                'summary' => new ScalarValueComparedToElementalValue(
                    'actual',
                    'includes',
                    new ElementIdentifier('.value')
                ),
                'expectedSummary' =>
                    '* <comment>actual</comment> does not include the value of element '
                    . '<comment>$".value"</comment> identified by:'
                ,
            ],
            'excludes' => [
                'summary' => new ScalarValueComparedToElementalValue(
                    'expected',
                    'excludes',
                    new ElementIdentifier('.value')
                ),
                'expectedSummary' =>
                    '* <comment>expected</comment> does not exclude the value of element '
                    . '<comment>$".value"</comment> identified by:'
                ,
            ],
            'matches' => [
                'summary' => new ScalarValueComparedToElementalValue(
                    'actual',
                    'matches',
                    new ElementIdentifier('.value')
                ),
                'expectedSummary' =>
                    '* <comment>actual</comment> does not match regular expression the value of element '
                    . '<comment>$".value"</comment> identified by:'
                ,
            ],
        ];
    }
}
