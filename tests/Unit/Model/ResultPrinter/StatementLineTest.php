<?php

declare(strict_types=1);

namespace webignition\BasilRunner\Tests\Unit\Model\ResultPrinter;

use webignition\BasilModels\Action\Action;
use webignition\BasilRunner\Model\ResultPrinter\StatementLine\StatementLine;
use webignition\BasilRunner\Model\TestOutput\Status;
use webignition\BasilRunner\Tests\Unit\AbstractBaseTest;

class StatementLineTest extends AbstractBaseTest
{
    /**
     * @dataProvider renderDataProvider
     */
    public function testRender(StatementLine $statementLine, string $expectedRenderedString)
    {
        $this->assertSame($expectedRenderedString, $statementLine->render());
    }

    public function renderDataProvider(): array
    {
        $statement = new Action(
            'click $".selector"',
            'click',
            '$".selector'
        );

        return [
            'success' => [
                'statementLine' => new StatementLine(
                    $statement,
                    Status::SUCCESS
                ),
                'expectedRenderedString' => '    <icon-success /> click $".selector"',
            ],
            'failure' => [
                'statementLine' => new StatementLine(
                    $statement,
                    Status::FAILURE
                ),
                'expectedRenderedString' =>
                    '    <icon-failure /> <highlighted-failure>click $".selector"</highlighted-failure>',
            ],
        ];
    }
}
