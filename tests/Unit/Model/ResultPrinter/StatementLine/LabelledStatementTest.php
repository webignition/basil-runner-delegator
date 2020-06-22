<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter\StatementLine;

use webignition\BasilModels\Action\Action;
use webignition\BasilRunner\Model\ResultPrinter\StatementLine\LabelledStatement;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class LabelledStatementTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(LabelledStatement $labelledStatement, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $labelledStatement->render());
    }

    public function renderDataProvider(): array
    {
        return [
            'default' => [
                'labelledStatement' => new LabelledStatement(
                    'label',
                    new Action(
                        'click $".selector"',
                        'click',
                        '$".selector'
                    )
                ),
                'expectedRenderedString' => '<comment>> label:</comment> click $".selector"',
            ],
        ];
    }
}
